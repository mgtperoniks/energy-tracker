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
import random
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
# Stagger start to avoid RS485 bus collision (seconds).
STARTUP_DELAY       = int(os.getenv('STARTUP_DELAY', 0))
# Delay between individual register reads (seconds).
INTER_REG_DELAY     = float(os.getenv('INTER_REG_DELAY', 0.2))

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

# --- LAST KNOWN VALUE CACHE ---
LKV_CACHE = {
    'voltage': None,
    'current': None,
    'power_kw': None,
    'power_factor': None,
    'kwh_total': None
}

# --- REGISTER MAP ---
# Segmented for industrial stability
REG_BLOCK_1 = (3009, 18) # Current, Voltage
REG_BLOCK_2 = (3059, 2)  # kW
REG_BLOCK_3 = (3083, 2)  # PF
REG_BLOCK_4 = (3203, 4)  # Wh

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
    
    # --- TASK 7: BUFFER REPLAY HARDENING ---
    max_replay = 5 # Limit to 5 per cycle
    print("[{}] Processing offline buffer ({} files available, limit {})...".format(get_log_ts(), len(files), max_replay), flush=True)
    count = 0
    headers = {'X-Device-Token': DEVICE_TOKEN, 'Content-Type': 'application/json'}
    
    for f_path in files:
        if count >= max_replay: break 
        
        # Replay jitter to prevent API spikes
        time.sleep(random.uniform(0.1, 0.5))
        
        try:
            with open(f_path, 'r') as f:
                payload = json.load(f)
            
            response = requests.post(LARAVEL_API_URL, json=payload, headers=headers, timeout=5)
            if response.status_code in [200, 201]:
                print("[{}] [REPLAY OK] {}".format(get_log_ts(), os.path.basename(f_path)), flush=True)
                os.remove(f_path)
                count += 1
            else:
                print("[{}] [REPLAY ERROR] Status: {}".format(get_log_ts(), response.status_code), flush=True)
                break 
        except Exception as e:
            print("[{}] [REPLAY EXCEPTION] {}".format(get_log_ts(), e), flush=True)
            break
            
    if count > 0:
        print("[{}] Flushed {} buffered payloads.".format(get_log_ts(), count), flush=True)

def sanitize_float(val):
    if val is None: return None
    if math.isnan(val) or math.isinf(val): return None
    return val

def poll_meter():
    global LAST_KWH_READING, LAST_POLL_TIME, LAST_METER_BOOT_ID, LAST_TELEMETRY, STALE_COUNT, OFFLINE_COUNT, LKV_CACHE
    
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT, timeout=2, framer=MODBUS_FRAMER)
    
    if not client.connect():
        print("[{}] STATUS: OFFLINE (Connection failed)".format(get_log_ts()), flush=True)
        client.close()
        OFFLINE_COUNT += 1
        return None

    try:
        OFFLINE_COUNT = 0 
        
        def safe_read(addr, count):
            try:
                rr = client.read_holding_registers(address=addr, count=count, device_id=PHYSICAL_SLAVE_ID)
                if rr.isError(): return None
                return rr.registers
            except Exception as e:
                if DEBUG_RAW_REGISTERS: print("[{}] READ ERR at {}: {}".format(get_log_ts(), addr, e), flush=True)
                return None

        # --- TASK 3: REDUCE MODBUS BLOCK SIZE (Segmented) ---
        b1 = safe_read(3009, 18)
        time.sleep(INTER_REG_DELAY)
        b2 = safe_read(3059, 2)
        time.sleep(INTER_REG_DELAY)
        b3 = safe_read(3083, 2)
        time.sleep(INTER_REG_DELAY)
        b4 = safe_read(3203, 4)

        def extract_float(regs, base, target):
            if regs is None: return None
            idx = target - base
            if idx < 0 or idx + 1 >= len(regs): return None
            try:
                raw = struct.pack('>HH', regs[idx], regs[idx+1])
                return struct.unpack('>f', raw)[0]
            except: return None

        def extract_int64(regs, base, target):
            if regs is None: return None
            idx = target - base
            if idx < 0 or idx + 3 >= len(regs): return None
            try:
                raw = struct.pack('>HHHH', regs[idx], regs[idx+1], regs[idx+2], regs[idx+3])
                return struct.unpack('>q', raw)[0]
            except: return None

        # --- EXTRACTION ---
        amps   = extract_float(b1, 3009, 3009)
        volts  = extract_float(b1, 3009, 3025)
        kw     = extract_float(b2, 3059, 3059)
        pf     = extract_float(b3, 3083, 3083)
        wh_raw = extract_int64(b4, 3203, 3203)
        
        kwh_total = wh_raw / 1000.0 if wh_raw is not None else None
        
        meter_replaced = False
        if LAST_METER_BOOT_ID and LAST_METER_BOOT_ID != METER_BOOT_ID:
            if kwh_total is not None and LKV_CACHE['kwh_total'] is not None and kwh_total < LKV_CACHE['kwh_total']:
                print("[{}] [METER REPLACEMENT] Resetting LKV.".format(get_log_ts()), flush=True)
                meter_replaced = True
                LKV_CACHE['kwh_total'] = None

        # --- TASK 8: QUALITY FLAG PRIORITY ---
        # Priority: OFFLINE > STALE > PARTIAL > GOOD
        quality = "GOOD"
        partial_failures = []
        
        def lkv_check(val, key, failures):
            if val is None:
                cached = LKV_CACHE[key]
                if cached is not None:
                    failures.append(key)
                    return cached
                return None
            LKV_CACHE[key] = val
            return val

        volts = lkv_check(volts, 'voltage', partial_failures)
        amps  = lkv_check(amps, 'current', partial_failures)
        kw    = lkv_check(kw, 'power_kw', partial_failures)
        pf    = lkv_check(pf, 'power_factor', partial_failures)
        kwh_total = lkv_check(kwh_total, 'kwh_total', partial_failures)

        if partial_failures:
            quality = "PARTIAL"

        if b1 is None and b2 is None and b3 is None and b4 is None:
            quality = "OFFLINE"

        # --- TASK 6: IMPROVE STALE DETECTION (Tolerance) ---
        def is_nearly_equal(a, b, eps=0.01):
            if a is None or b is None: return a == b
            return abs(a - b) < eps

        current_vals = [kwh_total, kw, volts, amps, pf]
        if LAST_TELEMETRY and quality != "OFFLINE":
            all_stale = True
            for i in range(len(current_vals)):
                if not is_nearly_equal(current_vals[i], LAST_TELEMETRY[i]):
                    all_stale = False
                    break
            
            if all_stale:
                STALE_COUNT += 1
                if STALE_COUNT > STALE_THRESHOLD:
                    # Only upgrade to STALE if not already OFFLINE
                    if quality != "OFFLINE":
                        quality = "STALE"
            else:
                STALE_COUNT = 0
        
        LAST_TELEMETRY = current_vals

        data = {
            'slave_id':          REPORT_AS_SLAVE_ID,
            'meter_boot_id':     METER_BOOT_ID,
            'kwh_total':         round(sanitize_float(kwh_total), 3) if kwh_total is not None else None,
            'power_kw':          round(sanitize_float(kw), 3) if kw is not None else None,
            'voltage':           round(sanitize_float(volts), 1) if volts is not None else None,
            'current':           round(sanitize_float(amps), 2) if amps is not None else None,
            'power_factor':      round(sanitize_float(pf), 3) if pf is not None else None,
            'meter_replaced':    meter_replaced,
            'telemetry_quality': quality,
            'is_offline':        (quality == "OFFLINE")
        }

        LAST_METER_BOOT_ID = METER_BOOT_ID
        return data

    except Exception as e:
        print("[{}] POLL EXCEPTION: {}".format(get_log_ts(), e), flush=True)
        return None
    finally:
        client.close()

def main():
    global OFFLINE_COUNT
    lock_handle = acquire_lock()
    print("====================================", flush=True)
    print("FINAL INDUSTRIAL SCALING PATCH ACTIVE", flush=True)
    print("====================================", flush=True)
    print("Slave ID: {}, Target: {}:{}".format(PHYSICAL_SLAVE_ID, MODBUS_IP, MODBUS_PORT), flush=True)
    print("====================================", flush=True)

    if STARTUP_DELAY > 0:
        time.sleep(STARTUP_DELAY)

    poll_count = 0
    while True:
        # --- TASK 5: RANDOMIZED JITTER ---
        time.sleep(random.uniform(0.2, 1.2))
        
        start_time = time.time()
        poll_count += 1
        
        process_buffer()
        
        data = poll_meter()
        
        if data is None:
            # --- TASK 12: POLLER SURVIVABILITY MODE ---
            payload = {
                'slave_id': REPORT_AS_SLAVE_ID,
                'meter_boot_id': METER_BOOT_ID,
                'kwh_total': LKV_CACHE['kwh_total'],
                'power_kw': None, 'voltage': None, 'current': None, 'power_factor': None,
                'is_offline': True,
                'telemetry_quality': 'OFFLINE'
            }
        else:
            payload = data

        elapsed = time.time() - start_time
        payload['poll_duration_sec'] = round(elapsed, 3)

        try:
            headers = {'X-Device-Token': DEVICE_TOKEN, 'Content-Type': 'application/json'}
            # Industrial timeout 10s
            response = requests.post(LARAVEL_API_URL, json=payload, headers=headers, timeout=10)
            if response.status_code in [200, 201]:
                print("[{}] [API OK] #{} Quality={}".format(get_log_ts(), poll_count, payload['telemetry_quality']), flush=True)
            else:
                print("[{}] [API ERR] Status: {}".format(get_log_ts(), response.status_code), flush=True)
                save_to_buffer(payload)
        except Exception as e:
            print("[{}] [API FAIL] {}".format(get_log_ts(), e), flush=True)
            save_to_buffer(payload)

        update_heartbeat("ONLINE" if not payload.get('is_offline') else "OFFLINE", elapsed, payload.get('kwh_total'))
        
        # --- TASK 4: CIRCUIT BREAKER ---
        if OFFLINE_COUNT >= RECOVERY_THRESHOLD:
            print("[{}] [CIRCUIT BREAKER] Sleeping 30s to prevent RS485 bus collapse.".format(get_log_ts()), flush=True)
            time.sleep(30)
            OFFLINE_COUNT = 0

        gc.collect() 
        sleep_time = max(1, INTERVAL_SECONDS - (time.time() - start_time))
        time.sleep(sleep_time)

if __name__ == "__main__":
    main()