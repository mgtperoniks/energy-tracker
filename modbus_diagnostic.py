import serial
import minimalmodbus

SERIAL_PORT = 'COM3'

print("== SINGLE METER READ TEST (Slave 1) ==\n")

instrument = minimalmodbus.Instrument(SERIAL_PORT, 1)
instrument.serial.baudrate = 9600
instrument.serial.parity = serial.PARITY_NONE
instrument.serial.timeout = 1.5
instrument.mode = minimalmodbus.MODE_RTU

try:
    kwh = instrument.read_float(74, functioncode=3, number_of_registers=2)
    volt = instrument.read_float(3009, functioncode=3, number_of_registers=2)
    amp = instrument.read_float(3017, functioncode=3, number_of_registers=2)
    kw = instrument.read_float(3045, functioncode=3, number_of_registers=2)
    pf = instrument.read_float(3083, functioncode=3, number_of_registers=2)
    
    print(f"  kWh Total  : {kwh:.2f} kWh")
    print(f"  Voltage    : {volt:.1f} V")
    print(f"  Current    : {amp:.2f} A")
    print(f"  Power      : {kw:.3f} kW")
    print(f"  Power Factor: {pf:.3f}")
    print(f"\n  ✓ ALL READS SUCCESSFUL!")
except Exception as e:
    print(f"  ✗ Error: {e}")

instrument.serial.close()
