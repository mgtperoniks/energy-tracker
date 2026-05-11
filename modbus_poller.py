from pymodbus.client import ModbusTcpClient
import requests
import time
import os
import struct
import math
import sys
import json
import gc
import glob
from datetime import datetime

# Linux-only locking
try:
    import fcntl
except ImportError:
    fcntl = None

# --- CONFIGURATION (via Environment Variables) ---
MODBUS_IP           = os.getenv('MODBUS_IP', '10.88.8.16')
MODBUS_PORT         = int(os.getenv('MODBUS_PORT', 502))
PHYSICAL_SLAVE_ID   = int(os.getenv('MODBUS_SLAVE_ID', 3))
REPORT_AS_SLAVE_ID  = int(os.getenv('REPORT_AS_SLAVE_ID', 3))
METER_BOOT_ID       = os.getenv('METER_BOOT_ID', 'PM3-DEFAULT')
LARAVEL_API_URL     = os.getenv('MODBUS_API_URL', 'http://localhost/api/readings')
DEVICE_TOKEN        = os.getenv('DEVICE_TOKEN', '') 
INTERVAL_SECONDS    = int(os.getenv('POLLING_INTERVAL', 300))
DEBUG_RAW_REGISTERS = os.getenv('DEBUG_RAW_REGISTERS', 'false').lower() == 'true'
# FRAMER: 'socket' (default Modbus TCP) or 'rtu' (Modbus RTU over TCP)
MODBUS_FRAMER       = os.getenv('MODBUS_FRAMER', 'socket').lower()

# --- FILE PATHS ---
LOCK_FILE      = "/tmp/modbus_slave_{}.lock".format(PHYSICAL_SLAVE_ID)
BUFFER_DIR     = "storage/offline-buffer"
HEARTBEAT_FILE = "storage/poller-heartbeat-slave-{}.json".format(PHYSICAL_SLAVE_ID)

# --- GLOBAL STATE ---
LAST_KWH_READING    = None
LAST_POLL_TIME      = None
LAST_METER_BOOT_ID  = None
LAST_TELEMETRY      = None
STALE_COUNT         = 0
OFFLINE_COUNT       = 0
STALE_THRESHOLD     = 6
RECOVERY_THRESHOLD  = 5

# --- REGISTER MAP (Validated) ---
REG_TOTAL_WH  = 3203   # INT64, 4 registers, Wh
REG_AVG_VOLT  = 3025   # Float32, 2 registers, V
REG_AVG_AMP   = 3009   # Float32, 2 registers, A
REG_TOTAL_KW  = 3059   # Float32, 2 registers, kW
REG_PF        = 3083   # Float32, 2 registers, PF

def get_log_ts():
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def acquire_lock():
    if fcntl is None:
        return # Skip on non-linux
    
    try:
        f = open(LOCK_FILE, 'w')
        fcntl.lockf(f, fcntl.LOCK_EX | fcntl.LOCK_NB)
        f.write(str(os.getpid()))
        f.flush()
        # Keep file handle open to maintain lock
        return f
    except (IOError, OSError):
        print("[{}] [BOOT] Duplicate poller detected for Slave {}. EXITING.".format(get_log_ts(), PHYSICAL_SLAVE_ID), flush=True)
        sys.exit(1)

def update_heartbeat(status, duration, kwh):
    try:
        if not os.path.exists("storage"): os.makedirs("storage")
        hb = {
            "slave_id": PHYSICAL_SLAVE_ID,
            "last_poll": get_log_ts(),
            "status": status,
            "poll_duration_sec": round(duration, 2),
            "last_kwh": kwh
        }
        with open(HEARTBEAT_FILE, 'w') as f:
            json.dump(hb, f)
    except Exception as e:
        print("[{}] HEARTBEAT ERROR: {}".format(get_log_ts(), e), flush=True)

def save_to_buffer(payload):
    try:
        if not os.path.exists(BUFFER_DIR): os.makedirs(BUFFER_DIR)
        filename = "failed_{}.json".format(datetime.now().strftime("%Y%m%d_%H%M%S"))
        path = os.path.join(BUFFER_DIR, filename)
        with open(path, 'w') as f:
            json.dump(payload, f)
        print("[{}] Telemetry buffered: {}".format(get_log_ts(), filename), flush=True)
    except Exception as e:
        print("[{}] BUFFER SAVE ERROR: {}".format(get_log_ts(), e), flush=True)

def process_buffer():
    if not os.path.exists(BUFFER_DIR): return
    files = sorted(glob.glob(os.path.join(BUFFER_DIR, "*.json")))
    if not files: return
    
    print("[{}] Processing offline buffer ({} files)...".format(get_log_ts(), len(files)), flush=True)
    count = 0
    headers = {'X-Device-Token': DEVICE_TOKEN, 'Content-Type': 'application/json'}
    
    for f_path in files:
        if count >= 20: break # Max 20 per cycle
        try:
            with open(f_path, 'r') as f:
                payload = json.load(f)
            
            response = requests.post(LARAVEL_API_URL, json=payload, headers=headers, timeout=5)
            if response.status_code == 200:
                os.remove(f_path)
                count += 1
            else:
                break # Stop on first failure
        except Exception as e:
            if DEBUG_RAW_REGISTERS:
                print("[{}] BUFFER SEND FAILED: {}".format(get_log_ts(), e), flush=True)
            break
            
    if count > 0:
        print("[{}] Successfully flushed {} files from buffer.".format(get_log_ts(), count), flush=True)

def sanitize_float(val):
    if val is None: return None
    if math.isnan(val) or math.isinf(val): return None
    return val

def read_float(client, address, slave):
    try:
        rr = client.read_holding_registers(address=address, count=2, slave=slave)
        if rr.isError():
            if DEBUG_RAW_REGISTERS:
                print("[{}] MODBUS READ ERROR at {}: {}".format(get_log_ts(), address, rr), flush=True)
            return None
        raw = rr.registers
        if DEBUG_RAW_REGISTERS:
            print("[{}] DEBUG REG {} RAW: {}".format(get_log_ts(), address, raw), flush=True)
        raw_bytes = struct.pack('>HH', raw[0], raw[1])
        value = struct.unpack('>f', raw_bytes)[0]
        return value
    except Exception as e:
        if DEBUG_RAW_REGISTERS:
            print("[{}] MODBUS EXCEPTION at {}: {}".format(get_log_ts(), address, e), flush=True)
        return None

def read_int64(client, address, slave):
    try:
        rr = client.read_holding_registers(address=address, count=4, slave=slave)
        if rr.isError():
            if DEBUG_RAW_REGISTERS:
                print("[{}] MODBUS READ ERROR at {}: {}".format(get_log_ts(), address, rr), flush=True)
            return None
        raw = rr.registers
        if DEBUG_RAW_REGISTERS:
            print("[{}] DEBUG REG {} RAW: {}".format(get_log_ts(), address, raw), flush=True)
        value_bytes = struct.pack('>HHHH', raw[0], raw[1], raw[2], raw[3])
        value = struct.unpack('>q', value_bytes)[0]
        return value
    except Exception as e:
        if DEBUG_RAW_REGISTERS:
            print("[{}] MODBUS EXCEPTION at {}: {}".format(get_log_ts(), address, e), flush=True)
        return None

def poll_meter():
    global LAST_KWH_READING, LAST_POLL_TIME, LAST_METER_BOOT_ID, LAST_TELEMETRY, STALE_COUNT, OFFLINE_COUNT
    
    # In Pymodbus 3.x, ModbusTcpClient accepts 'rtu' or 'socket' as framer string
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT, timeout=5, framer=MODBUS_FRAMER)
    
    if not client.connect():
        print("[{}] STATUS: OFFLINE (Cannot connect to {}:{})".format(get_log_ts(), MODBUS_IP, MODBUS_PORT), flush=True)
        client.close()
        OFFLINE_COUNT += 1
        return None

    try:
        OFFLINE_COUNT = 0 # Reset on success
        wh_total = read_int64(client, REG_TOTAL_WH, PHYSICAL_SLAVE_ID)
        kw       = read_float(client, REG_TOTAL_KW, PHYSICAL_SLAVE_ID)
        amps     = read_float(client, REG_AVG_AMP, PHYSICAL_SLAVE_ID)
        volts    = read_float(client, REG_AVG_VOLT, PHYSICAL_SLAVE_ID)
        pf       = read_float(client, REG_PF, PHYSICAL_SLAVE_ID)
        
        all_failed = (wh_total is None and kw is None and amps is None and volts is None and pf is None)
        if all_failed:
            print("[{}] STATUS: OFFLINE (All registers failed)".format(get_log_ts()), flush=True)
            return None
            
        if wh_total is None:
            print("[{}] STATUS: CORRUPTED (Energy register failed)".format(get_log_ts()), flush=True)
            return None
            
        kwh_total = wh_total / 1000.0
        data = {
            'slave_id':      REPORT_AS_SLAVE_ID,
            'meter_boot_id': METER_BOOT_ID,
            'kwh_total':     round(sanitize_float(kwh_total), 3),
            'power_kw':      round(sanitize_float(kw), 3) if kw is not None else None,
            'voltage':       round(sanitize_float(volts), 1) if volts is not None else None,
            'current':       round(sanitize_float(amps), 2) if amps is not None else None,
            'power_factor':  round(sanitize_float(pf), 3) if pf is not None else None,
            'meter_replaced': False,
            'stale_telemetry': False
        }

        # --- STALE TELEMETRY DETECTION ---
        current_vals = [data['kwh_total'], data['power_kw'], data['voltage'], data['current'], data['power_factor']]
        if LAST_TELEMETRY == current_vals:
            STALE_COUNT += 1
            if STALE_COUNT > STALE_THRESHOLD:
                print("[{}] [STALE TELEMETRY WARNING] Frozen data detected.".format(get_log_ts()), flush=True)
                data['stale_telemetry'] = True
        else:
            STALE_COUNT = 0
        LAST_TELEMETRY = current_vals

        # --- REPLACEMENT DETECTION ---
        now_ts = time.time()
        if LAST_METER_BOOT_ID and LAST_METER_BOOT_ID != METER_BOOT_ID:
            if LAST_KWH_READING and data['kwh_total'] < LAST_KWH_READING:
                print("[{}] [METER REPLACEMENT DETECTED] {} -> {}".format(get_log_ts(), LAST_METER_BOOT_ID, METER_BOOT_ID), flush=True)
                data['meter_replaced'] = True
                LAST_KWH_READING = data['kwh_total']
                LAST_POLL_TIME = now_ts
                LAST_METER_BOOT_ID = METER_BOOT_ID
                return data

        # --- GROWTH VALIDATION ---
        if LAST_KWH_READING and data['kwh_total']:
            growth = data['kwh_total'] - LAST_KWH_READING
            if growth >= 0 and growth < 500: # Simple bound
                LAST_KWH_READING = data['kwh_total']
                LAST_POLL_TIME = now_ts
            else:
                print("[{}] WARNING: Anomalous growth ({:.3f})".format(get_log_ts(), growth), flush=True)
        else:
            LAST_KWH_READING = data['kwh_total']
            LAST_POLL_TIME = now_ts
        
        LAST_METER_BOOT_ID = METER_BOOT_ID
        return data

    except Exception as e:
        print("[{}] POLL ERROR: {}".format(get_log_ts(), e), flush=True)
        return None
    finally:
        client.close()

def main():
    global OFFLINE_COUNT
    lock_handle = acquire_lock()
    print("====================================", flush=True)
    print("INDUSTRIAL HISTORIAN POLLER ACTIVE", flush=True)
    print("====================================", flush=True)
    print("Slave ID      : {}".format(PHYSICAL_SLAVE_ID), flush=True)
    print("Report ID     : {}".format(REPORT_AS_SLAVE_ID), flush=True)
    print("Boot ID       : {}".format(METER_BOOT_ID), flush=True)
    print("Target        : {}:{}".format(MODBUS_IP, MODBUS_PORT), flush=True)
    print("API Target    : {}".format(LARAVEL_API_URL), flush=True)
    print("Polling       : {} sec".format(INTERVAL_SECONDS), flush=True)
    print("Framer        : {}".format(MODBUS_FRAMER), flush=True)
    print("Debug Raw     : {}".format("Enabled" if DEBUG_RAW_REGISTERS else "Disabled"), flush=True)
    print("Register Map  : E={}, P={}, V={}, I={}, PF={}".format(
        REG_TOTAL_WH, REG_TOTAL_KW, REG_AVG_VOLT, REG_AVG_AMP, REG_PF
    ), flush=True)
    print("====================================", flush=True)

    poll_count = 0
    while True:
        start_time = time.time()
        poll_count += 1
        
        process_buffer()
        
        data = poll_meter()
        status = "ONLINE" if data else "OFFLINE"
        last_kwh = data['kwh_total'] if data else LAST_KWH_READING
        
        payload = data if data else {
            'slave_id': REPORT_AS_SLAVE_ID,
            'meter_boot_id': METER_BOOT_ID,
            'kwh_total': None,
            'power_kw': None,
            'voltage': None,
            'current': None,
            'power_factor': None,
            'is_offline': True
        }

        try:
            headers = {'X-Device-Token': DEVICE_TOKEN, 'Content-Type': 'application/json'}
            response = requests.post(LARAVEL_API_URL, json=payload, headers=headers, timeout=10)
            if response.status_code == 200:
                print("[{}] #{}: API OK".format(get_log_ts(), poll_count), flush=True)
            else:
                save_to_buffer(payload)
        except Exception:
            save_to_buffer(payload)

        elapsed = time.time() - start_time
        update_heartbeat(status, elapsed, last_kwh)
        print("[{}] [POLL] Duration: {:.2f} sec".format(get_log_ts(), elapsed), flush=True)
        
        # --- RECOVERY MODE ---
        if OFFLINE_COUNT >= RECOVERY_THRESHOLD:
            print("[{}] [RECOVERY MODE] Cooling down 30s due to multiple failures.".format(get_log_ts()), flush=True)
            time.sleep(30)
            OFFLINE_COUNT = 0

        gc.collect() # Memory safety
        time.sleep(max(1, INTERVAL_SECONDS - (time.time() - start_time)))

if __name__ == "__main__":
    main()