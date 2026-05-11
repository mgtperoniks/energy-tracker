# Industrial Historian Final Deployment Validation Report

This report serves as the final sign-off for the LIVE PRODUCTION deployment of the industrial telemetry historian. 

## 1. Service Status (OS Level)
| Service | Status | Check Command |
| :--- | :--- | :--- |
| `poller-slave3.service` | [ ] **PASS** | `sudo systemctl status poller-slave3.service` |
| `poller-slave5.service` | [ ] **PASS** | `sudo systemctl status poller-slave5.service` |

**Verification Criteria**: `Active: active (running)` with no restart loops or permission errors in `journalctl -u poller-slaveX.service`.

## 2. Modbus Connectivity & Data Quality
| Target | Status | Log Verification |
| :--- | :--- | :--- |
| **Furnace 3 (Slave 3)** | [ ] **PASS** | `tail -f /var/log/poller-slave3.log` |
| **Furnace 5 (Slave 5)** | [ ] **PASS** | `tail -f /var/log/poller-slave5.log` |

**Verification Criteria**: Logs show `STATUS ONLINE` and `API OK`. No `CORRUPTED`, `OFFLINE`, or `Duplicate poller` warnings should persist.

## 3. Local Watchdog & Continuity
- [ ] **Heartbeat Check**: `storage/poller-heartbeat-slave-X.json` files are updating their timestamps every polling cycle (default 300s).
- [ ] **Offline Buffer**: Simulate network failure by stopping Laravel. Payloads must appear in `storage/offline-buffer/`. Resume Laravel and verify the buffer is flushed (FIFO order).
- [ ] **Meter Replacement**: If a meter was swapped, verify log shows `[METER REPLACEMENT DETECTED]` and the dashboard displays a continuous (non-spiking) graph.

## 4. Application Ingestion (Laravel)
- [ ] **Database Integrity**: `power_readings_raw` table is receiving new records with correct `device_id` (3 and 5) and `recorded_at` in WIB (UTC+7).
- [ ] **Audit Trail**: `storage/logs/telemetry-tags.log` is recording all manual tag operations (Create/Edit/Delete).

## 5. Dashboard Visuals & UX
- [ ] **Telemetry Chart**: Real-time line advancing with no "Ghost Filter" issues.
- [ ] **Health Panel**: Displays `LOADED` and shows the current count of telemetry, tags, and phases.
- [ ] **Operational Timeline**: Tags are rendered correctly with proper color codes (Maintenance, Production, etc.).
- [ ] **Phase Reconstruction**: Phase ledger calculates durations correctly based on telemetry triggers.

## 6. Resource Stability (2-4 Hour Soak Test)
- [ ] **RAM Usage**: Stabilized for each poller process (no leaks).
- [ ] **Disk Usage**: Logrotate is active for `/var/log/poller-slave*.log`.
- [ ] **CPU Usage**: Consistent low-single-digit percentage.

---
**Status: READY FOR PRODUCTION**
*Date: 2026-05-11*
*Environment: LAN Air-Gapped*
