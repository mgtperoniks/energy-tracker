from pymodbus.client import ModbusTcpClient
import requests
import time
import os
from datetime import datetime
import struct

# --- CONFIGURATION (via Environment Variables) ---
MODBUS_IP = os.getenv('MODBUS_IP', '10.88.8.16')
MODBUS_PORT = int(os.getenv('MODBUS_PORT', 502))
PHYSICAL_SLAVE_ID = int(os.getenv('MODBUS_SLAVE_ID', 1))
REPORT_AS_SLAVE_ID = int(os.getenv('REPORT_AS_SLAVE_ID', 3))
# For Docker, use service name 'web' or the external IP/domain
LARAVEL_API_URL = os.getenv('MODBUS_API_URL', 'http://web/api/readings')
INTERVAL_SECONDS = int(os.getenv('POLLING_INTERVAL', 600))  # Default 10 minutes

# --- REGISTER MAP (PM2200) ---
REG_TOTAL_KWH = 74
REG_AVG_VOLT  = 3009
REG_AVG_AMP   = 3017
REG_TOTAL_KW  = 3045
REG_PF        = 3083

# --- READ FLOAT (Modbus 2 Register → Float32) ---
def read_float(client, address, slave):
    try:
        rr = client.read_holding_registers(
            address=address,
            count=2,
            slave=slave
        )

        if rr.isError():
            return None

        raw = rr.registers
        # Default: Big Endian (ABCD)
        value = struct.unpack('>f', struct.pack('>HH', raw[0], raw[1]))[0]
        return value

    except Exception:
        return None

# --- POLL DEVICE ---
def poll_meter():
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT, timeout=5)

    if not client.connect():
        print(f"[{datetime.now()}] STATUS: OFFLINE (Cannot connect to {MODBUS_IP})", flush=True)
        return None

    try:
        kwh   = read_float(client, REG_TOTAL_KWH, PHYSICAL_SLAVE_ID)
        
        if kwh is None:
            print(f"[{datetime.now()}] STATUS: NO RESPONSE (Connected but failed to read registers)", flush=True)
            return None

        kw    = read_float(client, REG_TOTAL_KW, PHYSICAL_SLAVE_ID)
        amps  = read_float(client, REG_AVG_AMP, PHYSICAL_SLAVE_ID)
        volts = read_float(client, REG_AVG_VOLT, PHYSICAL_SLAVE_ID)
        pf    = read_float(client, REG_PF, PHYSICAL_SLAVE_ID)

        data = {
            'slave_id': REPORT_AS_SLAVE_ID,
            'kwh_total': round(kwh, 2),
            'power_kw': round(kw, 3) if kw is not None else 0,
            'voltage': round(volts, 1) if volts is not None else 0,
            'current': round(amps, 2) if amps is not None else 0,
            'power_factor': round(pf, 3) if pf is not None else 1.0
        }

        return data

    except Exception as e:
        print(f"[{datetime.now()}] ERROR polling: {e}")
        return None

    finally:
        client.close()

# --- MAIN LOOP ---
def main():
    print("=== Energy Tracker Modbus TCP Poller ===", flush=True)
    print(f"Target: {MODBUS_IP}:{MODBUS_PORT} (Slave {PHYSICAL_SLAVE_ID})", flush=True)
    print(f"API Target: {LARAVEL_API_URL}", flush=True)
    print(f"Polling every {INTERVAL_SECONDS} seconds\n", flush=True)

    poll_count = 0

    while True:
        start_time = time.time()
        poll_count += 1

        data = poll_meter()
        # If no data (Offline), we send a 0 kW reading to the API to show "Mati"
        payload = data if data else {
            'slave_id': REPORT_AS_SLAVE_ID,
            'kwh_total': None, # API will use last known value
            'power_kw': 0,
            'voltage': 0,
            'current': 0,
            'power_factor': 0,
            'is_offline': True
        }

        try:
            response = requests.post(
                LARAVEL_API_URL,
                json=payload,
                timeout=10
            )

            if response.status_code == 200:
                status_msg = f"SUCCESS → {payload.get('kwh_total')} kWh" if data else "REPORTED OFFLINE"
                print(f"[{datetime.now()}] #{poll_count} {status_msg}", flush=True)
            else:
                print(f"[{datetime.now()}] API ERROR {response.status_code} → {response.text}", flush=True)

        except Exception as e:
            print(f"[{datetime.now()}] API FAILED: {e}", flush=True)


        elapsed = time.time() - start_time
        sleep_time = max(1, INTERVAL_SECONDS - elapsed)
        time.sleep(sleep_time)

if __name__ == "__main__":
    main()