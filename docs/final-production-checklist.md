# Industrial Historian Final Production Deployment Checklist

## 1. Database & Migrations
- [ ] Run `php artisan migrate --force`
- [ ] Verify `power_readings_raw` has unique index `unique_device_timestamp`
- [ ] Verify `operational_event_tags` has `deleted_at`, `deleted_by`, `delete_reason`
- [ ] Verify `tagging_audit_logs` table exists

## 2. Environment & Cache
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan optimize`

## 3. Services & Background Processes
- [ ] Restart Python Modbus pollers: `pm2 restart all` (or equivalent)
- [ ] Restart Laravel Queue Workers: `php artisan queue:restart`
- [ ] Verify USR-TCP232-410s connectivity (4 gateways)

## 4. Governance Verification
- [ ] Login as `adminqcflange@peroniks.com` or `adminqcfitting@peroniks.com`
- [ ] Verify "Save Tag" and "Forensic Delete" buttons are VISIBLE
- [ ] Verify Delete action REQUIRES 10-char reason
- [ ] Login as other user
- [ ] Verify "READONLY" badge is visible and buttons are HIDDEN

## 5. Telemetry & Historian Verification
- [ ] Open Dashboard, verify 12H cache loads correctly
- [ ] Verify 4H sliding window pan is smooth (Windows 7 Safe Mode)
- [ ] Verify no "Ghost Panning" (Memory leak check)
- [ ] Verify "OPEN" phases do not have negative durations or usage

## 6. Audit & Export Verification
- [ ] Create a test tag, edit it, then delete it with reason
- [ ] Export "Tagging Audit Logs" to Excel
- [ ] Verify Excel contains: `deleted_at`, `delete_reason`, and User Emails
- [ ] Verify `tagging_audit_logs` table captured all 3 events

## 7. Rollback Plan
- [ ] Backup database: `mysqldump energy_tracker > pre_hardening_backup.sql`
- [ ] Rollback migrations: `php artisan migrate:rollback --step=2`
- [ ] Clear caches: `php artisan cache:clear`

## 8. Final Confirmation
- [ ] Readiness: READY FOR PRODUCTION DEPLOYMENT
- [ ] Sign-off: Industrial Historian Hardening Patch 1.2.0
