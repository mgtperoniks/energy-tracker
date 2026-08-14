import struct
import sys
from pymodbus.client import ModbusTcpClient

gateway_ip = '10.88.8.17'

print(f"=== FULL MODBUS SCAN ON GATEWAY {gateway_ip} (IDs 31-247) ===", flush=True)
client = ModbusTcpClient(gateway_ip, port=502, timeout=1.0)
if client.connect():
    print(f"Connected to gateway {gateway_ip}", flush=True)
    for slave_id in range(31, 248):
        # Progress indicator every 20 IDs
        if slave_id % 20 == 0:
            print(f"  Scanning ID {slave_id}...", flush=True)
        try:
            r = client.read_holding_registers(address=3059, count=2, device_id=slave_id)
            if not r.isError():
                raw = struct.pack('>HH', r.registers[0], r.registers[1])
                val = struct.unpack('>f', raw)[0]
                print(f"\n  [FOUND] Slave {slave_id} → Active Power: {val:.3f} kW\n", flush=True)
        except Exception:
            pass
    client.close()
    print("Full scan complete.", flush=True)
else:
    print(f"Failed to connect to gateway {gateway_ip}", flush=True)
