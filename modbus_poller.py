import minimalmodbus
import serial
import requests
import time
import json
from datetime import datetime

# --- CONFIGURATION ---
SERIAL_PORT = 'COM3'  # USB-SERIAL CH340
BAUD_RATE = 9600      # Matched to Power Meter setting
PARITY = serial.PARITY_NONE  # Matched to Power Meter setting
PHYSICAL_SLAVE_ID = 1  # The actual Slave ID on the physical Power Meter
REPORT_AS_SLAVE_ID = 3  # Report to Laravel as Slave ID 3 (Machine: COR PASIR, PM-03)
LARAVEL_API_URL = 'http://localhost/energy-tracker/public/index.php/api/readings'
INTERVAL_SECONDS = 60  # Poll every 1 minute (30 min = 30 data points)

# --- REGISTER MAP (Float32, FC=03) ---
# Discovered via register scan on actual Power Meter
REG_TOTAL_KWH = 74    # Total Active Energy (kWh)
REG_AVG_VOLT  = 3009  # Average Voltage L-N (V)
REG_AVG_AMP   = 3017  # Average Current (A) - max of phases
REG_TOTAL_KW  = 3045  # Total Active Power (kW)
REG_PF        = 3083  # Power Factor

def poll_meter(physical_slave_id):
    try:
        instrument = minimalmodbus.Instrument(SERIAL_PORT, physical_slave_id)
        instrument.serial.baudrate = BAUD_RATE
        instrument.serial.parity = PARITY
        instrument.serial.timeout = 1.5
        instrument.mode = minimalmodbus.MODE_RTU
        
        # Read Float32 (2 registers each)
        kwh = instrument.read_float(REG_TOTAL_KWH, functioncode=3, number_of_registers=2)
        kw = instrument.read_float(REG_TOTAL_KW, functioncode=3, number_of_registers=2)
        amps = instrument.read_float(REG_AVG_AMP, functioncode=3, number_of_registers=2)
        volts = instrument.read_float(REG_AVG_VOLT, functioncode=3, number_of_registers=2)
        pf = instrument.read_float(REG_PF, functioncode=3, number_of_registers=2)
        
        instrument.serial.close()
        
        return {
            'slave_id': REPORT_AS_SLAVE_ID,  # Report as Machine 3 for demo
            'kwh_total': round(kwh, 2),
            'power_kw': round(kw, 3),
            'voltage': round(volts, 1),
            'current': round(amps, 2),
            'power_factor': round(pf, 3)
        }
    except Exception as e:
        print(f"[{datetime.now()}] Error polling Slave ID {physical_slave_id}: {str(e)}")
        try:
            instrument.serial.close()
        except:
            pass
        return None

def main():
    print(f"--- Energy Tracker Modbus Poller (DEMO MODE) ---")
    print(f"Target: {SERIAL_PORT} @ {BAUD_RATE}bps")
    print(f"Physical Slave: {PHYSICAL_SLAVE_ID} → Report as Slave: {REPORT_AS_SLAVE_ID}")
    print(f"Polling every {INTERVAL_SECONDS}s\n")

    poll_count = 0
    while True:
        start_time = time.time()
        poll_count += 1
        
        data = poll_meter(PHYSICAL_SLAVE_ID)
        if data:
            try:
                response = requests.post(LARAVEL_API_URL, json=data, timeout=5)
                if response.status_code == 200:
                    print(f"[{datetime.now()}] #{poll_count} PM-03: {data['kwh_total']} kWh, {data['power_kw']} kW, {data['voltage']}V, {data['current']}A, PF={data['power_factor']} -> Success")
                else:
                    print(f"[{datetime.now()}] #{poll_count} PM-03: API Error {response.status_code} - {response.text}")
            except requests.exceptions.RequestException as e:
                print(f"[{datetime.now()}] #{poll_count} API Connection Failed: {str(e)}")
        
        # Wait for the next interval
        elapsed = time.time() - start_time
        wait_time = max(0, INTERVAL_SECONDS - elapsed)
        time.sleep(wait_time)

if __name__ == "__main__":
    main()
