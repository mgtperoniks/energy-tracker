# -*- coding: utf-8 -*-
"""
environmental_poller.py
=======================
Polls SHT40 temperature/humidity sensors via USR TCP232 gateways
and inserts readings into the Laravel energy_tracker database.

Follows the same pattern as modbus_poller.py.

Configuration via environment variables:
  DB_HOST                      MySQL host          (default: 127.0.0.1)
  DB_PORT                      MySQL port          (default: 3306)
  DB_DATABASE                  MySQL database      (default: energy_tracker)
  DB_USERNAME                  MySQL user          (default: root)
  DB_PASSWORD                  MySQL password      (default: 123456788)
  ENVIRONMENTAL_POLL_INTERVAL  Poll interval sec   (default: 900)
"""

import os
import time
import socket
import struct
import pymysql
from datetime import datetime

# ---------------------------------------------------------------------------
# CONFIGURATION (via environment variables - same pattern as modbus_poller.py)
# ---------------------------------------------------------------------------
DB_HOST       = os.getenv("DB_HOST",     "127.0.0.1")
DB_PORT       = int(os.getenv("DB_PORT", 3306))
DB_NAME       = os.getenv("DB_DATABASE", "energy_tracker")
DB_USER       = os.getenv("DB_USERNAME", "root")
DB_PASS       = os.getenv("DB_PASSWORD", "123456788")
POLL_INTERVAL = int(os.getenv("ENVIRONMENTAL_POLL_INTERVAL", 900))

MODBUS_TIMEOUT = 3.0

# ---------------------------------------------------------------------------
# SENSOR DEFINITIONS (both USR TCP232 confirmed working)
# ---------------------------------------------------------------------------
SENSORS = [
    {
        "name":      "SHT40 Lab (USR 10.88.8.17)",
        "device_id": 23,
        "host":      "10.88.8.17",
        "port":      502,
        "protocol":  "tcp",
        "slave_id":  1,
        "fc":        0x04,
        "start_reg": 0x0001,
        "quantity":  2,
    },
    {
        "name":      "SHT40 Field (USR 10.88.8.20)",
        "device_id": 28,
        "host":      "10.88.8.20",
        "port":      502,
        "protocol":  "rtu",
        "slave_id":  1,
        "fc":        0x04,
        "start_reg": 0x0001,
        "quantity":  2,
    },
]

# ---------------------------------------------------------------------------
# MODBUS HELPERS
# ---------------------------------------------------------------------------

def _crc16(data):
    crc = 0xFFFF
    for b in data:
        crc ^= b
        for _ in range(8):
            if crc & 0x0001:
                crc = (crc >> 1) ^ 0xA001
            else:
                crc >>= 1
    return crc


def _build_rtu(slave_id, fc, start_reg, quantity):
    pdu = struct.pack(">BBHH", slave_id, fc, start_reg, quantity)
    crc = _crc16(pdu)
    return pdu + struct.pack("<H", crc)


def _build_tcp(slave_id, fc, start_reg, quantity):
    """Modbus TCP MBAP frame (proven working with USR 10.88.8.17)."""
    return struct.pack(">HHHBBHH",
                       0x0001, 0x0000, 0x0006,
                       slave_id, fc, start_reg, quantity)


def _recv(sock, n, timeout):
    sock.settimeout(timeout)
    data = b""
    deadline = time.time() + timeout
    while len(data) < n:
        rem = deadline - time.time()
        if rem <= 0:
            break
        sock.settimeout(rem)
        chunk = sock.recv(n - len(data))
        if not chunk:
            break
        data += chunk
    return data


def read_sensor(sensor):
    protocol  = sensor["protocol"]
    slave_id  = sensor["slave_id"]
    fc        = sensor["fc"]
    start_reg = sensor["start_reg"]
    quantity  = sensor["quantity"]

    if protocol == "tcp":
        tx           = _build_tcp(slave_id, fc, start_reg, quantity)
        expected_len = 9 + quantity * 2
    else:
        tx           = _build_rtu(slave_id, fc, start_reg, quantity)
        expected_len = 5 + quantity * 2

    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.settimeout(MODBUS_TIMEOUT)
    try:
        sock.connect((sensor["host"], sensor["port"]))
        sock.sendall(tx)
        rx = _recv(sock, expected_len, MODBUS_TIMEOUT)
    finally:
        sock.close()

    if not rx or len(rx) < 4:
        raise RuntimeError("No/short response ({} bytes)".format(len(rx)))

    if protocol == "tcp":
        regs_bytes = rx[9:]
    else:
        if rx[1] & 0x80:
            raise RuntimeError("Modbus exception: {}".format(rx[2]))
        regs_bytes = rx[3:3 + rx[2]]

    regs = [struct.unpack(">H", regs_bytes[i:i+2])[0]
            for i in range(0, len(regs_bytes) - 1, 2)]

    if len(regs) < 2:
        raise RuntimeError("Not enough registers: {}".format(len(regs)))

    return regs[0] / 10.0, regs[1] / 10.0   # temperature, humidity


# ---------------------------------------------------------------------------
# DATABASE
# ---------------------------------------------------------------------------

def db_insert(device_id, temperature, humidity, recorded_at):
    conn = pymysql.connect(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        database=DB_NAME, charset="utf8mb4", connect_timeout=5,
    )
    try:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO environmental_readings "
                "(device_id, temperature, humidity, recorded_at) "
                "VALUES (%s, %s, %s, %s)",
                (device_id, temperature, humidity, recorded_at),
            )
        conn.commit()
    finally:
        conn.close()


# ---------------------------------------------------------------------------
# LOGGING
# ---------------------------------------------------------------------------

def log(msg):
    print("[{}] {}".format(datetime.now().strftime("%Y-%m-%d %H:%M:%S"), msg), flush=True)


# ---------------------------------------------------------------------------
# POLL CYCLE
# ---------------------------------------------------------------------------

def poll_all():
    recorded_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    for sensor in SENSORS:
        name = sensor["name"]
        try:
            temp, hum = read_sensor(sensor)
            db_insert(sensor["device_id"], temp, hum, recorded_at)
            log("[OK]   {} => T={:.1f}C  H={:.1f}%RH  DB=OK".format(name, temp, hum))
        except Exception as e:
            log("[FAIL] {} => {}".format(name, e))


def main():
    log("=" * 60)
    log("ENVIRONMENTAL POLLER - SHT40 via USR TCP232")
    log("Sensors  : {}".format(len(SENSORS)))
    log("Interval : {}s ({} min)".format(POLL_INTERVAL, POLL_INTERVAL // 60))
    log("DB       : {}@{}:{}/{}".format(DB_USER, DB_HOST, DB_PORT, DB_NAME))
    log("=" * 60)

    poll_cycle = 0
    while True:
        poll_cycle += 1
        log("--- Poll #{} ---".format(poll_cycle))
        poll_all()
        log("Sleeping {}s until next poll...".format(POLL_INTERVAL))
        time.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    main()
