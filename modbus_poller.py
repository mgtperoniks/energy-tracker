from pymodbus.client import ModbusTcpClient
import requests
import time
import os
import struct
from datetime import datetime

# --- CONFIGURATION (via Environment Variables) ---
MODBUS_IP         = os.getenv('MODBUS_IP', '10.88.8.16')
MODBUS_PORT       = int(os.getenv('MODBUS_PORT', 502))
PHYSICAL_SLAVE_ID = int(os.getenv('MODBUS_SLAVE_ID', 1))
REPORT_AS_SLAVE_ID = int(os.getenv('REPORT_AS_SLAVE_ID', 3))
LARAVEL_API_URL   = os.getenv('MODBUS_API_URL', 'http://web/api/readings')
INTERVAL_SECONDS  = int(os.getenv('POLLING_INTERVAL', 600))  # Default 10 minutes


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
REG_AVG_VOLT  = 3009   # Float32, 2 registers, unit: V
REG_AVG_AMP   = 3017   # Float32, 2 registers, unit: A
REG_TOTAL_KW  = 3045   # Float32, 2 registers, unit: kW
REG_PF        = 3083   # Float32, 2 registers, unit: (dimensionless)


# --- READ FLOAT32 (2 Registers → IEEE 754 Float) ---
def read_float(client, address, slave):
    """Read a 32-bit IEEE 754 float from 2 consecutive Modbus registers."""
    try:
        rr = client.read_holding_registers(address=address, count=2, slave=slave)
        if rr.isError():
            print(f"[{datetime.now()}] MODBUS ERROR at Reg {address}: {rr}", flush=True)
            return None
        raw = rr.registers
        # Big-Endian word order: High word first (ABCD)
        value = struct.unpack('>f', struct.pack('>HH', raw[0], raw[1]))[0]
        return value
    except Exception as e:
        print(f"[{datetime.now()}] EXCEPTION at Reg {address}: {e}", flush=True)
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

        data = {
            'slave_id':     REPORT_AS_SLAVE_ID,
            'kwh_total':    round(kwh_total, 3),
            'power_kw':     round(kw,    3) if kw    is not None else 0,
            'voltage':      round(volts, 1) if volts is not None else 0,
            'current':      round(amps,  2) if amps  is not None else 0,
            'power_factor': round(pf,    3) if pf    is not None else 1.0,
        }

        print(
            f"[{datetime.now()}] READ OK → "
            f"E={kwh_total:.2f} kWh | "
            f"P={data['power_kw']} kW | "
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
            'power_kw':     0,
            'voltage':      0,
            'current':      0,
            'power_factor': 0,
            'is_offline':   True,
        }

        try:
            response = requests.post(LARAVEL_API_URL, json=payload, timeout=10)

            if response.status_code == 200:
                if data:
                    print(
                        f"[{datetime.now()}] #{poll_count} API OK → "
                        f"{payload['kwh_total']} kWh stored",
                        flush=True
                    )
                else:
                    print(f"[{datetime.now()}] #{poll_count} OFFLINE reported to API", flush=True)
            else:
                print(
                    f"[{datetime.now()}] #{poll_count} API ERROR {response.status_code} → {response.text[:200]}",
                    flush=True
                )

        except Exception as e:
            print(f"[{datetime.now()}] #{poll_count} API FAILED: {e}", flush=True)

        elapsed    = time.time() - start_time
        sleep_time = max(1, INTERVAL_SECONDS - elapsed)
        time.sleep(sleep_time)


if __name__ == "__main__":
    main()