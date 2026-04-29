---
description: 
---

# Energy-Tracker: AI Agent Handover Document

Dokumen ini adalah ringkasan arsitektur, status, dan aturan bisnis dari proyek **Energy-Tracker**.
Gunakan dokumen ini sebagai sumber kebenaran utama (source of truth) saat melanjutkan pengembangan.

---

## 1. Project Identity

- **Nama Project:** Energy-Tracker

- **Tujuan Utama:**
  Sistem monitoring energi industri berbasis hybrid-meter architecture dengan ingestion O(1), anomaly detection, accounting cost layer, threshold engine, tariff engine, dan historical analytics.

- **Status Project Saat Ini:**
  Phase 1D.4 selesai.
  Panel Device Configuration & Poller Logs Explorer aktif.
  Audit Trail untuk konfigurasi sistem sudah terintegrasi.

- **Deployment Target:**
  Air-gapped / Offline-first Industrial LAN Environment

  Stack:

  - Ubuntu Server
  - Laravel Application
  - MySQL / MariaDB
  - Poller Service (Python) terpisah
  - Nginx / Apache

---

## 2. Current Active Phase

Current Status:

Phase 1D.4 completed

Next Target:

Deployment Hardening & Security Phase 1E

Pending Minimum Deployable:

- Device Configuration Panel
- Poller Logs Explorer
- Deployment Hardening
- Backup Strategy

---

## 3. Core Architecture

Arsitektur final menggunakan tiered storage architecture.

### Ingestion Layer

Poller eksternal (Python) membaca Modbus meter.

Polling interval production:

1 minute

Flow:

Modbus Meter → Poller → Laravel API → O(1) Upsert

Tidak boleh ada heavy query di ingestion.

---

### Data Tiering

#### Raw Layer

Table:

power_readings_raw

Function:

Forensic operational layer

Resolution:

1 minute

Retention:

365 days

Use case:

- anomaly investigation
- operator audit
- forensic diagnostics

---

#### Hourly Layer

Table:

power_readings_hourly

Function:

compressed operational history

Resolution:

1 hour

Retention:

1825 days (5 years)

Use case:

- historical load analysis
- operational comparison

---

#### Daily Layer

Table:

power_readings_daily

Function:

accounting source of truth

Resolution:

1 day

Retention:

forever

Use case:

- accounting
- yearly comparison
- audit
- budgeting

---

### Auto Reset Normalization

Physical meter can reset.

System stores:

meter_kwh_raw

System normalizes into:

kwh_total

Formula:

normalized_total = active_baseline_kwh + meter_kwh_raw

This logic is critical.

Never remove.

---

### Tariff Engine

Tariff source of truth:

effective_date

Not:

is_active

is_active is only UI helper.

Historical tariff must remain immutable.

---

### Threshold Engine

Hierarchy:

Device Override
↓
Global Default

Source:

settings table

Access layer:

SettingService

---

### Chart Query Layer

Dynamic datasource switching:

0–4 hours → Raw

12–24 hours → Hourly

7 days+ → Daily

Purpose:

prevent OOM

---

Architecture Flow:

```text
[Modbus Meter]
      ↓
[Poller Service]
      ↓
[Laravel API]
      ↓
[power_readings_raw]
      ↓
[Anomaly Engine]
      ↓
[poller_logs]

Raw → Hourly Scheduler → power_readings_hourly
Hourly → Daily Scheduler → power_readings_daily 
4. Database Map
devices

Function:

physical meter registry

Important fields:

active_baseline_kwh
last_kwh_total
last_power_kw
monitor_idle_consumption
is_online
last_seen_at
machines

Function:

logical industrial asset

Architecture:

1 machine can have multiple devices

power_readings_raw

Function:

forensic raw storage

Important fields:

device_id
meter_kwh_raw
kwh_total
power_kw
voltage
current
power_factor
recorded_at
power_readings_hourly

Function:

hourly aggregation

Important fields:

kwh_usage
avg_power_kw
min_power_kw
max_power_kw
sample_count
power_readings_daily

Function:

daily accounting source

Important fields:

kwh_usage
energy_cost
total_sample_count
poller_logs

Function:

system event logs

Stores:

warnings
anomalies
poller errors
meter_resets

Function:

meter reset audit history

Stores:

reset history
baseline transition history
electricity_tariffs

Function:

historical tariff storage

Important fields:

rate_per_kwh
effective_date
is_active
settings

Function:

threshold storage

Supports:

global
device override
5. Data Retention Policy

Final Business Decision:

Raw

365 days

Reason:

forensic investigation

Hourly

1825 days

Reason:

long operational history

Daily

forever

Reason:

accounting history

Never prune.

6. Route Map
Overview

GET /

DashboardController@index

Monitoring

GET /monitoring/meters/{id}

MachineDashboardController@show

GET /monitoring/environmental

EnvironmentalController@index

GET /monitoring/system-health

SystemHealthController@index

Analytics

GET /analytics/operational

GET /analytics/accounting

GET /analytics/audit

Administration

GET|POST /admin/tariffs

GET|POST /admin/thresholds

GET /admin/device-config
Route::post('/admin/device-config', 'AdminController@updateDeviceConfig')
GET /admin/poller-logs
GET /admin/reset-history

Assets

/assets/departments

/assets/machines

/assets/devices

/assets/sensors

7. Controller Map

DashboardController

Global KPI

MachineDashboardController

Meter detail page

ReportController

Operational / Accounting / Audit

AdminController

Tariffs / Thresholds

AssetController

CRUD Assets

ChartController

JSON chart datasource

SystemHealthController

System telemetry dashboard

8. Service Map

EnergyChartService

dynamic chart datasource

TariffService

tariff synchronization

SettingService

threshold resolver

Future:

EnergyCostService

(optional)

9. Scheduler Map

energy:aggregate-hourly

every hour

energy:aggregate-daily

daily 00:01

prune:raw-readings

daily 02:00

prune:hourly-readings

daily 02:15

sync-tariffs

daily 00:01

health:detect-low-voltage

every 5 minutes

health:detect-idle-consumption

every 10 minutes

10. Anomaly Engine Map
Low Voltage Detector

Detect prolonged low voltage operation.

Threshold source:

SettingService

Idle Consumption Detector

Detect idle leakage.

Threshold source:

SettingService

Auto Reset Detector

Detect physical meter reset.

Threshold source:

SettingService

11. Administration Panel Map

Completed:

Tariffs
Thresholds
Device Config
Poller Logs Explorer

Pending:

Deployment Hardening
Backup Strategy
Reset History Explorer
12. Current UI Status

Dashboard

ACTIVE

Meter Detail

ACTIVE

Reports

ACTIVE

Admin Panels

ACTIVE

System Health

ACTIVE

13. Known Stable Routes (Smoke-tested)

Verified HTTP 200:

/
/monitoring/meters/{id}
/analytics/operational
/analytics/accounting
/analytics/audit
/admin/tariffs
/admin/thresholds
/admin/device-config
/admin/poller-logs
14. Known Constraints

Hybrid machine-meter architecture

Not every machine has dedicated meter.

Physical meter can reset.

Must normalize.

Accounting uses frozen daily cost.

Not realtime recalculated.

Idle monitoring opt-in.

Not all devices monitored.

15. Critical Business Rules

DO NOT CHANGE WITHOUT ARCHITECTURE REVIEW

effective_date is tariff source of truth
thresholds can be overridden per device
raw data is forensic truth
daily data is accounting truth
kwh_total must stay normalized
ingestion must remain O(1)
device historical attribution follows device (logical assignment), not machine snapshot.
16. Pending Tasks (Minimum Deployable)

Priority:

Device Configuration Panel
Poller Logs Explorer
Deployment Hardening
Backup Strategy
17. Forbidden Refactors

Do not refactor:

ingestion O(1)
tariff immutability
retention policy
normalized kwh_total logic
threshold hierarchy
18. Deployment Checklist
migrate database
validate .env
setup scheduler
validate storage permission
setup backup
run smoke test
19. Recovery Notes

If poller dies:

restart poller service

If meter resets:

auto reset detector or manual reset log

If tariff wrong:

create new tariff

never edit old tariff

If threshold wrong:

adjust via admin thresholds

never hardcode

END OF HANDOVER DOCUMENT