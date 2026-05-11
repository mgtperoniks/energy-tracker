# Industrial Historian Deployment Checklist

## 1. Meter Replacement Procedure
- [ ] **Stop Service**: Run `sudo systemctl stop poller-slaveX.service`.
- [ ] **Update Identity**: Edit `.env.slaveX` and update `METER_BOOT_ID` with a new unique identifier (e.g., `PM3-20260511-NEW`).
- [ ] **Physical Swap**: Perform hardware installation and Modbus TCP cabling.
- [ ] **Start Service**: Run `sudo systemctl start poller-slaveX.service`.
- [ ] **Verify Detection**: Check logs (`tail -f /var/log/poller-slaveX.log`) for `[METER REPLACEMENT DETECTED]`. This ensures the historian continuity is preserved without triggering "Negative Delta" errors.

## 2. Telemetry Validation
- [ ] **Boot Test**: Ensure the service doesn't crash on startup. `[BOOT] Startup validation PASSED` must appear in the logs.
- [ ] **Raw Audit**: If readings are suspect, set `DEBUG_RAW_REGISTERS=true` in `.env` and restart. Compare raw register hex/dec values with the meter's technical manual.
- [ ] **Load Consistency**: Verify `Power (kW)` matches the actual furnace operation (e.g., ~150-300kW during melt, <50kW during idle).

## 3. Historian Continuity & Stability
- [ ] **Polling Check**: Confirm `[POLL] Duration` is under 5 seconds for local LAN.
- [ ] **Dashboard Sync**: Open the historian web dashboard and verify the "Historian Health" panel shows `LOADED` and timestamps are advancing.
- [ ] **Audit Trail**: Check `storage/logs/telemetry-tags.log` on the Laravel server for any unauthorized sequence attempts or forced overrides.

## 4. Maintenance
- [ ] **Log Rotation**: Ensure `/var/log/poller-slaveX.log` is included in logrotate to prevent disk exhaustion.
- [ ] **Systemd Persistence**: Verify `Restart=always` is active by simulating a process kill.
