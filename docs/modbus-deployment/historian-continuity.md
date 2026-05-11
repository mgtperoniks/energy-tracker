# Historian Continuity & Operations Guide

This document defines the operational procedures for maintaining telemetry integrity in an air-gapped industrial environment.

## 1. Meter Lifecycle Management

### Replacing a Meter
1.  **Stop Poller**: `sudo systemctl stop poller-slaveX.service`.
2.  **Hardware Swap**: Replace physical meter, verify Modbus address.
3.  **Update Identity**: Change `METER_BOOT_ID` in `.env.slaveX` (e.g., `PM3-20260511-NEW`).
4.  **Restart**: `sudo systemctl start poller-slaveX.service`.
5.  **Verify**: Log should show `[METER REPLACEMENT DETECTED]`.

### Changing Slave ID
- Update `MODBUS_SLAVE_ID` in `.env`.
- Ensure the systemd service name and lock files are updated if tracking multiple slaves.

## 2. Telemetry Status Definitions

| Status | Meaning | Action |
| :--- | :--- | :--- |
| **ONLINE** | Poller communicating normally. | None. |
| **OFFLINE** | No TCP connection or timeout. | Check gateway/cabling. |
| **PARTIAL** | Some registers failed to read. | Check for RS485 noise. |
| **CORRUPTED** | Energy register (Wh) read error. | Critical: Data ignored to prevent spike. |
| **STALE** | Data hasn't changed for 6+ polls. | Check if meter is frozen or gateway bug. |

## 3. Data Integrity Flags

### `meter_replaced`
- **Trigger**: `METER_BOOT_ID` changes + kWh value decreases.
- **Effect**: Historian analytics treats this as a reset point, preventing "negative delta" calculations.

### `stale_telemetry`
- **Trigger**: 5 core values identical for >6 polling cycles.
- **Effect**: Warning flag for observability. Note: Normal if furnace is completely powered off.

## 4. Recovery & Buffering

### [RECOVERY MODE]
- Triggered after 5 consecutive OFFLINE polls.
- Forces a 30s cooldown and client recreation to clear potential gateway hangs.

### Offline Buffering
- If the API is unreachable, payloads are stored in `storage/offline-buffer/*.json`.
- The poller automatically retries up to 20 buffered files before each new poll cycle.
- Files are processed in FIFO order to maintain temporal sequence.

## 5. Audit & Continuity Steps
1.  Verify `poller-heartbeat-slave-X.json` for live status.
2.  Inspect `/var/log/poller-slaveX.log` for anomalous growth warnings.
3.  Cross-reference the historian dashboard "Health" panel with physical meter readings weekly.
