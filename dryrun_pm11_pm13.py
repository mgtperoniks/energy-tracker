import struct
import sys
from pymodbus.client import ModbusTcpClient

gateway_ip = '10.88.8.17'

print("=== STARTING DRY-RUN FOR PM-11 & PM-13 ===")
client = ModbusTcpClient(gateway_ip, port=502, timeout=3)
if client.connect():
    print(f"Connected to gateway {gateway_ip}")
    for slave_id in [11, 13]:
        try:
            r = client.read_holding_registers(address=3059, count=2, device_id=slave_id)
            if not r.isError():
                raw = struct.pack('>HH', r.registers[0], r.registers[1])
                val = struct.unpack('>f', raw)[0]
                print(f"  Slave {slave_id} → Modbus Read SUCCESS. Value Power (kW): {val:.3f}")
            else:
                print(f"  Slave {slave_id} → Modbus Read FAILED: {r}")
        except Exception as e:
            print(f"  Slave {slave_id} → Error: {e}")
    client.close()
else:
    print(f"Failed to connect to gateway {gateway_ip}")
