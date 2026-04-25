---
description: Ringkasan parameter eksperimen Modbus dan hal-hal yang perlu dihindari untuk skalabilitas
---

# 📊 Ringkasan Eksperimen Modbus (Power Meter)

Dokumen ini mencatat parameter teknis yang telah berhasil diuji coba pada koneksi Power Meter (PM-03/COR PASIR) menggunakan RS485-to-USB.

## 🛠️ Parameter Koneksi (Serial)
Gunakan setting ini sebagai baseline untuk semua device di Site A:

| Parameter | Value | Catatan |
| :--- | :--- | :--- |
| **Port** | `COM3` | USB-SERIAL CH340 |
| **Baud Rate** | `9600` | Harus sinkron dengan setting fisik di Meter |
| **Parity** | `None` | `serial.PARITY_NONE` |
| **Stop Bits** | `1` | Default |
| **Byte Size** | `8` | Default |
| **Timeout** | `1.5s` | Krusial untuk menghindari tabrakan data (collision) |

## 📑 Register Map (Float32 / FC=03)
Semua data dibaca menggunakan Function Code `03` (Read Holding Registers) dengan format **Float32** (mengonsumsi 2 register/4 byte).

| Data | Register (Dec) | Unit |
| :--- | :--- | :--- |
| **Total Active Energy** | `74` | kWh |
| **Average Voltage (L-N)**| `3009` | V |
| **Average Current** | `3017` | A |
| **Total Active Power** | `3045` | kW |
| **Power Factor** | `3083` | - |

> [!NOTE]
> Library `minimalmodbus` menggunakan 0-based indexing. Jika di manual tertulis register `75`, maka di kode ditulis `74`.

## ⚠️ Hal yang WAJIB Dihindari
Jangan lakukan hal-hal berikut untuk mencegah kegagalan sistem atau pembacaan data yang salah:

1. **Over-polling**: Jangan mempolling data lebih cepat dari 5-10 detik jika nanti ada banyak slave (22 unit). Interval aman yang disarankan adalah **60 detik** per putaran (loop).
2. **Double Serial Handle**: Jangan membuka port serial di dua script berbeda secara bersamaan. Pastikan `modbus_diagnostic.py` mati sebelum menjalankan `modbus_poller.py`.
3. **Ghost Slaves**: Jangan menggunakan Slave ID `0` (Broadcast) karena tidak mengembalikan response.
4. **Mismatched Parity**: Jika satu meter di-set `Even`, maka semua meter di bus yang sama harus `Even`. Saat ini eksperimen menggunakan `None`.
5. **Kabel Terbalik**: Pastikan Pin A (D+) dan B (D-) tidak terbalik. Jika terbalik, lampu indikator RX/TX mungkin menyala tapi data akan error (Timeout).

## 🚀 Rencana Skalabilitas (Next Steps)
1. Modifikasi `modbus_poller.py` agar menerima array of Slave IDs.
2. Implementasi mekanisme *retry* (maksimal 3x) sebelum menandai node sebagai "Offline".
3. Integrasi ke Dashboard Laravel untuk monitoring real-time 22 node.

---

## ✅ Konfigurasi USR TCP Gateway (KRITIS)

Dokumen ini mencatat temuan penting saat commissioning USR TCP Gateway ke PM2200.

### Setting Serial Port (`http://10.88.8.16` → Serial Port)
| Parameter | Value | Catatan |
| :--- | :--- | :--- |
| **Baud Rate** | `9600` | Harus sama dengan setting di PM2200 |
| **Data Size** | `8` | Default |
| **Parity** | `None` | Harus sama dengan setting di PM2200 |
| **Stop Bits** | `1` | Default |
| **Local Port** | `502` | Standar Modbus TCP |
| **Work Mode** | `TCP Server` | Gateway menunggu koneksi dari Python |
| **Similar RFC2217** | `OFF` ❌ | Harus **dimatikan**, atau Modbus tidak akan berfungsi |

### ⚠️ PENYEBAB UTAMA `NO RESPONSE` — Wajib Diingat!
> **Expand Function → Modbus Type HARUS diset ke `Modbus TCP/RTU`**
>
> Jika dibiarkan `None`, USR gateway tidak melakukan konversi protokol antara
> Modbus TCP (dari Python) ke Modbus RTU (ke Power Meter via RS485).
> Koneksi TCP berhasil, tapi data tidak pernah sampai ke meter.

### Checklist Commissioning USR Gateway Baru
- [ ] Serial Port: Baudrate & Parity cocok dengan PM2200
- [ ] Similar RFC2217: **OFF**
- [ ] Expand Function → Modbus Type: **`Modbus TCP/RTU`**
- [ ] Save → Reboot USR

