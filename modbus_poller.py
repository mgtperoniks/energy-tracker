from pymodbus.client import ModbusTcpClient
import requests
import time
from datetime import datetime
import struct

# --- CONFIGURATION ---
MODBUS_IP = '10.88.8.16'
MODBUS_PORT = 502
PHYSICAL_SLAVE_ID = 1
REPORT_AS_SLAVE_ID = 3
LARAVEL_API_URL = 'http://localhost/energy-tracker/public/index.php/api/readings'
INTERVAL_SECONDS = 60

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
            device_id=slave
        )

        if rr.isError():
            print(f"[WARN] Read error at register {address}")
            return None

        raw = rr.registers

        # Default: Big Endian (ABCD)
        value = struct.unpack('>f', struct.pack('>HH', raw[0], raw[1]))[0]

        return value

    except Exception as e:
        print(f"[ERROR] Read exception at {address}: {e}")
        return None


# --- POLL DEVICE ---
def poll_meter():
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT)

    if not client.connect():
        print(f"[{datetime.now()}] ERROR: Cannot connect to {MODBUS_IP}")
        return None

    try:
        kwh   = read_float(client, REG_TOTAL_KWH, PHYSICAL_SLAVE_ID)
        kw    = read_float(client, REG_TOTAL_KW, PHYSICAL_SLAVE_ID)
        amps  = read_float(client, REG_AVG_AMP, PHYSICAL_SLAVE_ID)
        volts = read_float(client, REG_AVG_VOLT, PHYSICAL_SLAVE_ID)
        pf    = read_float(client, REG_PF, PHYSICAL_SLAVE_ID)

        data = {
            'slave_id': REPORT_AS_SLAVE_ID,
            'kwh_total': round(kwh, 2) if kwh is not None else None,
            'power_kw': round(kw, 3) if kw is not None else None,
            'voltage': round(volts, 1) if volts is not None else None,
            'current': round(amps, 2) if amps is not None else None,
            'power_factor': round(pf, 3) if pf is not None else None
        }

        return data

    except Exception as e:
        print(f"[{datetime.now()}] ERROR polling: {e}")
        return None

    finally:
        client.close()


# --- MAIN LOOP ---
def main():
    print("=== Energy Tracker Modbus TCP Poller ===")
    print(f"Target: {MODBUS_IP}:{MODBUS_PORT}")
    print(f"Polling every {INTERVAL_SECONDS} seconds\n")

    poll_count = 0

    while True:
        start_time = time.time()
        poll_count += 1

        data = poll_meter()

        if data:
            try:
                response = requests.post(
                    LARAVEL_API_URL,
                    json=data,
                    timeout=5
                )

                if response.status_code == 200:
                    print(f"[{datetime.now()}] #{poll_count} SUCCESS → {data}")
                else:
                    print(f"[{datetime.now()}] API ERROR {response.status_code} → {response.text}")

            except Exception as e:
                print(f"[{datetime.now()}] API FAILED: {e}")

        else:
            print(f"[{datetime.now()}] No data received")

        elapsed = time.time() - start_time
        time.sleep(max(0, INTERVAL_SECONDS - elapsed))


# --- ENTRY POINT ---
if __name__ == "__main__":
    main()