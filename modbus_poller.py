from pymodbus.client import ModbusTcpClient
import requests
import time
import os
import struct
import math
import math
import uuid
from datetime import datetime

# --- CONFIGURATION (via Environment Variables) ---
MODBUS_IP         = os.getenv('MODBUS_IP', '10.88.8.16')
MODBUS_PORT       = int(os.getenv('MODBUS_PORT', 502))
PHYSICAL_SLAVE_ID = int(os.getenv('MODBUS_SLAVE_ID', 1))
REPORT_AS_SLAVE_ID = int(os.getenv('REPORT_AS_SLAVE_ID', 3))
LARAVEL_API_URL   = os.getenv('MODBUS_API_URL', 'http://web/api/readings')
DEVICE_TOKEN      = os.getenv('DEVICE_TOKEN', '') # Required for authentication
INTERVAL_SECONDS  = int(os.getenv('POLLING_INTERVAL', 600))  # Default 10 minutes

# --- GLOBAL STATE (Local session) ---
LAST_KWH_READING = None
LAST_POLL_TIME    = None

# --- VALIDATION CONSTANTS ---
MAX_KW_CAPACITY = 400.0  # Furnace max load
GROWTH_THRESHOLD_KWH_PER_MIN = 10.0 # 1.5x max capacity for safety margin

# --- LOGGING UTILS ---
SESSION_ID = str(uuid.uuid4())[:8]
LOG_TS = lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S")


# --- REGISTER MAP (PM2200 EasyLogic) ---
# Source: Schneider Electric PM2200 Modbus Communication Guide
#
# IMPORTANT: Register 3203 = Active Energy Delivered (E Del)
#   - Data Type : INT64 (4 x 16-bit registers = 8 bytes)
#   - Unit      : Wh (Watt-hours) → must divide by 1000 to get kWh
#   - Endian    : Big-Endian (ABCD EFGH word order)
#
# Other registers (power, voltage, current, PF):
#   - Data Type : Float32 (2 x 16-bit registers)
#   - Endian    : Big-Endian (ABCD)
#
REG_TOTAL_WH  = 3203   # INT64, 4 registers, unit: Wh
REG_AVG_VOLT  = 3025   # CHANGED to 3025 (Voltage L-L Avg) for PM2220
REG_AVG_AMP   = 3009   # CHANGED to 3009 (Current Average) for PM2220
REG_TOTAL_KW  = 3059   # Active Power Total
REG_PF        = 3083   # Power Factor Total

# --- PM2220 HARDWARE CONSTANTS ---
CT_RATIO = 120.0  # 600/5

# --- CANDIDATE REGISTERS FOR VOLTAGE COMPARISON ---
VOLTAGE_CANDIDATES = {
    3009: "Original Mapping (Suspect)",
    3019: "Voltage L-L A-B",
    3025: "Voltage L-L Avg (Standard)",
    3027: "Voltage L-N Avg",
    3031: "Voltage L-L Avg (Alt)"
}

# --- CANDIDATE REGISTERS FOR CURRENT COMPARISON ---
CURRENT_CANDIDATES = {
    2999: "Current Phase A (Primary Suspect)",
    3001: "Current Phase B",
    3003: "Current Phase C",
    3009: "Current Average (Standard)",
    3011: "Current Average (Alt)",
    3017: "Current Unbalance % (Current Config)",
    3021: "Demand Current"
}

# --- CANDIDATE REGISTERS FOR POWER COMPARISON ---
# We will read these side-by-side to identify the correct one for furnace load tracking
POWER_CANDIDATES = {
    3027: "Active Power Total (Alt 1)",
    3045: "Current Mapping (Likely Wrong)",
    3053: "Active Power Total (Alt 2)",
    3059: "Active Power Total (Standard)",
    3067: "Active Power Total (Alt 3)"
}


# --- TELEMETRY VALIDATION ---
def validate_telemetry(data):
    """
    Apply defensive validation rules for furnace environment.
    Low power/current is OK (normal furnace cycle), but technical errors are not.
    """
    errors = []
    
    # 1. Power Factor Validation
    if data['power_factor'] is not None and data['power_factor'] > 1.0:
        errors.append(f"PF > 1 ({data['power_factor']})")
        # Do not force to 1.0 if invalid, keep as None to signify missing/invalid
        data['power_factor'] = None
        
    # 2. Voltage Collapse Validation
    if data['voltage'] is not None and 0 < data['voltage'] < 10:
        errors.append(f"Unrealistic Voltage ({data['voltage']}V)")
        data['voltage'] = None
        
    # 3. NaN Check (Handled by sanitize_float, but log here for explicit validation record)
    if data['power_kw'] is None:
        # We don't add to errors if it's null, as sanitize_float already logged it
        pass

    if errors:
        print(f"[{datetime.now()}] VALIDATION WARNING: {', '.join(errors)}", flush=True)
    
    return data


# --- SANITIZE FLOAT ---
def sanitize_float(val, name="Value"):
    """Replace NaN/Inf with None (null in JSON) and log if invalid."""
    if val is None:
        return None
    if math.isnan(val) or math.isinf(val):
        print(f"[{datetime.now()}] INVALID {name.upper()} DETECTED (NaN/Inf) → Setting to null", flush=True)
        return None
    return val


# --- READ FLOAT32 (2 Registers → IEEE 754 Float) ---
def read_float(client, address, slave, debug=False):
    """Read a 32-bit IEEE 754 float from 2 consecutive Modbus registers."""
    try:
        rr = client.read_holding_registers(address=address, count=2, slave=slave)
        if rr.isError():
            print(f"[{LOG_TS()}] MODBUS ERROR at Reg {address}: {rr}", flush=True)
            return None
        raw = rr.registers
        
        # Big-Endian word order: High word first (ABCD)
        raw_bytes = struct.pack('>HH', raw[0], raw[1])
        value = struct.unpack('>f', raw_bytes)[0]

        if debug:
            print(f"[{LOG_TS()}] DEBUG REG {address}: Raw={raw} | Bytes={raw_bytes.hex()} | Decoded={value}", flush=True)

        return value
    except Exception as e:
        print(f"[{LOG_TS()}] EXCEPTION at Reg {address}: {e}", flush=True)
        return None


# --- READ INT64 (4 Registers → 64-bit signed integer) ---
def read_int64(client, address, slave):
    """
    Read a 64-bit signed integer from 4 consecutive Modbus registers.
    PM2200 stores Energy as INT64 in Wh. Divide by 1000 to convert to kWh.
    Word order: Big-Endian (highest word first).
    """
    try:
        rr = client.read_holding_registers(address=address, count=4, slave=slave)
        if rr.isError():
            print(f"[{datetime.now()}] MODBUS ERROR at Reg {address} (INT64): {rr}", flush=True)
            return None
        raw = rr.registers
        # Combine 4 x 16-bit words into a single 64-bit integer (Big-Endian)
        # raw[0] = highest word, raw[3] = lowest word
        value_bytes = struct.pack('>HHHH', raw[0], raw[1], raw[2], raw[3])
        value = struct.unpack('>q', value_bytes)[0]  # 'q' = signed 64-bit integer
        return value
    except Exception as e:
        print(f"[{datetime.now()}] EXCEPTION at Reg {address} (INT64): {e}", flush=True)
        return None


# --- POLL DEVICE ---
def poll_meter():
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT, timeout=5)

    if not client.connect():
        print(f"[{datetime.now()}] STATUS: OFFLINE (Cannot connect to {MODBUS_IP})", flush=True)
        return None

    try:
        # Read Total Energy: INT64 in Wh → convert to kWh
        wh_total = read_int64(client, REG_TOTAL_WH, PHYSICAL_SLAVE_ID)

        if wh_total is None:
            print(f"[{datetime.now()}] STATUS: NO RESPONSE (Connected but failed to read energy register)", flush=True)
            return None

        kwh_total = wh_total / 1000.0  # Convert Wh → kWh

        # Read other parameters (Float32)
        kw    = read_float(client, REG_TOTAL_KW,  PHYSICAL_SLAVE_ID)
        amps  = read_float(client, REG_AVG_AMP,   PHYSICAL_SLAVE_ID)
        volts = read_float(client, REG_AVG_VOLT,  PHYSICAL_SLAVE_ID)
        pf    = read_float(client, REG_PF,        PHYSICAL_SLAVE_ID)

        # --- VOLTAGE REGISTER COMPARISON MODE ---
        print(f"[{LOG_TS()}] --- VOLTAGE REGISTER COMPARISON ---", flush=True)
        for addr, desc in VOLTAGE_CANDIDATES.items():
            val = read_float(client, addr, PHYSICAL_SLAVE_ID)
            status = f"{val:.1f} V" if val is not None else "ERROR"
            print(f"  Reg {addr} ({desc}): {status}", flush=True)
        print(f"[{LOG_TS()}] ------------------------------------", flush=True)

        # --- CURRENT REGISTER COMPARISON MODE ---
        print(f"[{LOG_TS()}] --- CURRENT REGISTER COMPARISON (CT Ratio: {CT_RATIO}) ---", flush=True)
        for addr, desc in CURRENT_CANDIDATES.items():
            val = read_float(client, addr, PHYSICAL_SLAVE_ID)
            if val is not None:
                scaled = val * CT_RATIO
                print(f"  Reg {addr} ({desc}): {val:.3f} (Secondary) | {scaled:.1f} A (Scaled Primary)", flush=True)
            else:
                print(f"  Reg {addr} ({desc}): ERROR", flush=True)
        print(f"[{LOG_TS()}] -----------------------------------------------------", flush=True)

        # Defensive validation & Sanitization
        kw_s    = sanitize_float(kw,    "power_kw")
        amps_s  = sanitize_float(amps,  "current")
        volts_s = sanitize_float(volts, "voltage")
        pf_s    = sanitize_float(pf,    "power_factor")
        kwh_s   = sanitize_float(kwh_total, "energy_kwh")

        data = {
            'slave_id':     REPORT_AS_SLAVE_ID,
            'kwh_total':    round(kwh_s, 3) if kwh_s is not None else None,
            'power_kw':     round(kw_s, 3) if kw_s is not None else None,
            'voltage':      round(volts_s, 1) if volts_s is not None else None,
            'current':      round(amps_s, 2) if amps_s is not None else None,
            'power_factor': round(pf_s, 3) if pf_s is not None else None,
        }

        # Apply Furnace-Specific Validation
        data = validate_telemetry(data)

        # --- PRIMARY PHYSICAL VALIDATION: ENERGY GROWTH ---
        global LAST_KWH_READING, LAST_POLL_TIME
        now_ts = time.time()
        growth_str = "First reading"
        
        if LAST_KWH_READING is not None and data['kwh_total'] is not None:
            growth = data['kwh_total'] - LAST_KWH_READING
            
            # Calculate time-adjusted threshold
            # Default to 1.0 if time diff is zero/negative to avoid division errors
            elapsed_min = max(0.01, (now_ts - LAST_POLL_TIME) / 60.0) if LAST_POLL_TIME else 1.0
            threshold = GROWTH_THRESHOLD_KWH_PER_MIN * elapsed_min
            
            if growth < 0:
                print(f"[{LOG_TS()}] WARNING: NEGATIVE ENERGY DELTA DETECTED ({growth:.3f} kWh). Meter reset or corruption suspected.", flush=True)
                growth_str = f"INVALID: Negative ({growth:.3f})"
                # Do NOT update baseline for growth calculation to recover from transient errors
            elif growth > threshold:
                print(f"[{LOG_TS()}] WARNING: UNREALISTIC ENERGY SPIKE DETECTED ({growth:.3f} kWh in {elapsed_min:.1f} min). Max allowed: {threshold:.1f} kWh.", flush=True)
                growth_str = f"INVALID: Spike ({growth:.3f})"
                # Do NOT update baseline
            else:
                growth_str = f"+{growth:.3f} kWh since last poll"
                LAST_KWH_READING = data['kwh_total']
                LAST_POLL_TIME    = now_ts
        else:
            # First reading or null energy
            if data['kwh_total'] is not None:
                LAST_KWH_READING = data['kwh_total']
                LAST_POLL_TIME    = now_ts

        print(
            f"[{LOG_TS()}] READ OK → "
            f"E={data['kwh_total'] if data['kwh_total'] is not None else 'N/A'} kWh ({growth_str}) | "
            f"P={data['power_kw'] if data['power_kw'] is not None else 'NaN'} kW | "
            f"V={data['voltage']} V | "
            f"I={data['current']} A | "
            f"PF={data['power_factor']}",
            flush=True
        )

        return data

    except Exception as e:
        print(f"[{datetime.now()}] ERROR polling: {e}", flush=True)
        return None

    finally:
        client.close()


# --- MAIN LOOP ---
def main():
    print("=== Energy Tracker Modbus TCP Poller ===", flush=True)
    print(f"Target       : {MODBUS_IP}:{MODBUS_PORT} (Slave ID: {PHYSICAL_SLAVE_ID})", flush=True)
    print(f"Report as    : Slave ID {REPORT_AS_SLAVE_ID}", flush=True)
    print(f"API Target   : {LARAVEL_API_URL}", flush=True)
    print(f"Interval     : {INTERVAL_SECONDS}s ({INTERVAL_SECONDS // 60} minutes)", flush=True)
    print(f"Energy Reg   : {REG_TOTAL_WH} (INT64, Wh → /1000 = kWh)", flush=True)
    print("", flush=True)

    poll_count = 0

    while True:
        start_time = time.time()
        poll_count += 1

        data = poll_meter()

        # If offline: send a marker with is_offline=True, kwh_total=None
        # Laravel will use the last known kwh_total from DB
        payload = data if data else {
            'slave_id':     REPORT_AS_SLAVE_ID,
            'kwh_total':    None,
            'power_kw':     None,
            'voltage':      None,
            'current':      None,
            'power_factor': None,
            'is_offline':   True,
        }

        try:
            headers = {
                'X-Device-Token': DEVICE_TOKEN,
                'Content-Type': 'application/json'
            }
            response = requests.post(LARAVEL_API_URL, json=payload, headers=headers, timeout=10)

            if response.status_code == 200:
                if data:
                    print(
                        f"[{LOG_TS()}] #{poll_count} API OK → "
                        f"{payload['kwh_total']} kWh stored",
                        flush=True
                    )
                else:
                    print(f"[{LOG_TS()}] #{poll_count} OFFLINE reported to API", flush=True)
            else:
                print(
                    f"[{LOG_TS()}] #{poll_count} API ERROR {response.status_code} → {response.text[:200]}",
                    flush=True
                )

        except Exception as e:
            print(f"[{LOG_TS()}] #{poll_count} API FAILED: {e}", flush=True)

        elapsed    = time.time() - start_time
        sleep_time = max(1, INTERVAL_SECONDS - elapsed)
        time.sleep(sleep_time)


if __name__ == "__main__":
    main()