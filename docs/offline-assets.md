# Offline Asset Documentation

This document tracks all third-party JavaScript and CSS assets that have been localized for offline usage within the industrial historian environment.

## 1. Date-Fns (Date Utility Library)
- **Version**: 3.6.0
- **Source URL**: `https://cdn.jsdelivr.net/npm/date-fns@3.6.0/cdn.min.js`
- **Local Path**: `public/assets/vendor/date-fns/date-fns.min.js`
- **Purpose**: Required by Chart.js for handling time scales and parsing ISO datetime strings robustly.
- **Update Procedure**: Download the updated `cdn.min.js` from jsDelivr and replace the local file.

## 2. Chart.js Adapter Date-Fns
- **Version**: 3.0.0
- **Source URL**: `https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js`
- **Local Path**: `public/assets/vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js`
- **Purpose**: Integrates `date-fns` natively into Chart.js scales to manage time-based X-axes.
- **Update Procedure**: Download the updated `chartjs-adapter-date-fns.bundle.min.js` from jsDelivr and replace the local file.

## 3. Flatpickr
- **Version**: N/A (Pre-existing local asset)
- **Local Path**: `public/assets/vendor/flatpickr/flatpickr.min.js`, `public/assets/vendor/flatpickr/flatpickr.min.css`
- **Purpose**: Offline-compatible 24h datetime picker for the forensic filter.

## 4. Chart.js & Plugins
- **Version**: N/A (Pre-existing local asset)
- **Local Path**: `public/assets/js/chart.js`, `public/assets/js/chartjs-plugin-annotation.min.js`
- **Purpose**: Core historian telemetry graphing and tagging UI overlays.

## Hardening Notes
All assets listed above are guaranteed to operate without resolving external DNS or establishing remote connections. This ensures strict conformity with air-gapped LAN conditions.
