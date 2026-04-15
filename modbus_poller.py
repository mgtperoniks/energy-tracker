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

# --- REGISTER MAP ---
REG_TOTAL_KWH = 74
REG_AVG_VOLT  = 3009
REG_AVG_AMP   = 3017
REG_TOTAL_KW  = 3045
REG_PF        = 3083

def read_float(client, address, slave):
    rr = client.read_holding_registers(address, 2, unit=slave)
    if rr.isError():
        return None
    
    raw = rr.registers
    # Convert 2 register → float32
    value = struct.unpack('>f', struct.pack('>HH', raw[0], raw[1]))[0]
    return value

def poll_meter():
    client = ModbusTcpClient(MODBUS_IP, port=MODBUS_PORT)

    if not client.connect():
        print("Gagal connect ke device")
        return None

    try:
        kwh = read_float(client, REG_TOTAL_KWH, PHYSICAL_SLAVE_ID)
        kw = read_float(client, REG_TOTAL_KW, PHYSICAL_SLAVE_ID)
        amps = read_float(client, REG_AVG_AMP, PHYSICAL_SLAVE_ID)
        volts = read_float(client, REG_AVG_VOLT, PHYSICAL_SLAVE_ID)
        pf = read_float(client, REG_PF, PHYSICAL_SLAVE_ID)

        return {
            'slave_id': REPORT_AS_SLAVE_ID,
            'kwh_total': round(kwh, 2) if kwh else 0,
            'power_kw': round(kw, 3) if kw else 0,
            'voltage': round(volts, 1) if volts else 0,
            'current': round(amps, 2) if amps else 0,
            'power_factor': round(pf, 3) if pf else 0
        }

    except Exception as e:
        print(f"[{datetime.now()}] Error: {str(e)}")
        return None

    finally:
        client.close()

def main():
    print(f"--- Energy Tracker Modbus TCP Poller ---")
    print(f"Target: {MODBUS_IP}:{MODBUS_PORT}")
    print(f"Polling every {INTERVAL_SECONDS}s\n")

    poll_count = 0

    while True:
        start_time = time.time()
        poll_count += 1

        data = poll_meter()

        if data:
            try:
                response = requests.post(LARAVEL_API_URL, json=data, timeout=5)
                if response.status_code == 200:
                    print(f"[{datetime.now()}] #{poll_count} OK → {data}")
                else:
                    print(f"[{datetime.now()}] API Error {response.status_code}")
            except Exception as e:
                print(f"[{datetime.now()}] API Failed: {str(e)}")

        elapsed = time.time() - start_time
        time.sleep(max(0, INTERVAL_SECONDS - elapsed))

if __name__ == "__main__":
    main()