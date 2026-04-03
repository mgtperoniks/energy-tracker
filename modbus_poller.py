import minimalmodbus
import requests
import time
import json
from datetime import datetime

# --- CONFIGURATION ---
SERIAL_PORT = 'COM3'  # Change this to your USB-to-RS485 COM port (e.g., COM4, COM5)
BAUD_RATE = 19200     # Default for PM2220 is usually 19200 or 9600
SLAVE_IDS = range(1, 23)  # Meters PM-01 to PM-22 (Slave IDs 1 to 22)
LARAVEL_API_URL = 'http://localhost/energy-tracker/public/index.php/api/readings' # Update if hosted elsewhere
INTERVAL_SECONDS = 60  # Poll every 1 minute

# --- SCHNEIDER PM2220 REGISTER MAP (Float32) ---
# Reading 2 registers (32-bit float) using Function Code 03
REG_TOTAL_KWH = 3060  # Total Active Energy (kWh)
REG_TOTAL_KW  = 3054  # Total Active Power (kW)
REG_AVG_AMP   = 3028  # Average Current (A)
REG_AVG_VOLT  = 3036  # Average Voltage L-L (V)

def poll_meter(slave_id):
    try:
        instrument = minimalmodbus.Instrument(SERIAL_PORT, slave_id)
        instrument.serial.baudrate = BAUD_RATE
        instrument.serial.timeout = 0.5
        instrument.mode = minimalmodbus.MODE_RTU
        
        # Read Float32 (2 registers)
        kwh = instrument.read_float(REG_TOTAL_KWH, functioncode=3, number_of_registers=2)
        kw = instrument.read_float(REG_TOTAL_KW, functioncode=3, number_of_registers=2)
        amps = instrument.read_float(REG_AVG_AMP, functioncode=3, number_of_registers=2)
        volts = instrument.read_float(REG_AVG_VOLT, functioncode=3, number_of_registers=2)
        
        return {
            'slave_id': slave_id,
            'kwh_total': round(kwh, 2),
            'power_kw': round(kw, 3),
            'voltage': round(volts, 1),
            'current': round(amps, 2),
            'power_factor': 1.0 # Optional: can read from reg 3084
        }
    except Exception as e:
        print(f"[{datetime.now()}] Error polling Slave ID {slave_id}: {str(e)}")
        return None

def main():
    print(f"--- Energy Tracker Modbus Poller Started ---")
    print(f"Target: {SERIAL_PORT} @ {BAUD_RATE}bps")
    print(f"Polling {len(SLAVE_IDS)} meters every {INTERVAL_SECONDS}s\n")

    while True:
        start_time = time.time()
        
        for slave_id in SLAVE_IDS:
            data = poll_meter(slave_id)
            if data:
                try:
                    response = requests.post(LARAVEL_API_URL, json=data, timeout=5)
                    if response.status_code == 200:
                        print(f"[{datetime.now()}] PM-{slave_id:02d}: {data['kwh_total']} kWh -> Success")
                    else:
                        print(f"[{datetime.now()}] PM-{slave_id:02d}: API Error {response.status_code}")
                except requests.exceptions.RequestException as e:
                    print(f"[{datetime.now()}] API Connection Failed: {str(e)}")
        
        # Wait for the next interval
        elapsed = time.time() - start_time
        wait_time = max(0, INTERVAL_SECONDS - elapsed)
        time.sleep(wait_time)

if __name__ == "__main__":
    main()
