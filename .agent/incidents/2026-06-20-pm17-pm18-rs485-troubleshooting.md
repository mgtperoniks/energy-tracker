# Incident Report: PM17 & PM18 Modbus Connection Failure
**Tanggal Kejadian:** 20 Juni 2026  
**Status:** Resolved (Terselesaikan)  
**Komponen Terdampak:** Telemetry Ingestion (Power Meter 17 & 18)

---

## 1. Ringkasan Kejadian
Saat melakukan commissioning awal untuk mengintegrasikan dua power meter baru, yaitu **PM-17 (HEAT TREATMENT)** dan **PM-18 (KIMIA FITTING)** pada USR TCP Gateway baru (`10.88.8.18`), sistem gagal menerima telemetri. Data konsumsi energi tidak muncul di dashboard Laravel dan poller daemon mencatat kegagalan pembacaan Modbus secara terus-menerus.

---

## 2. Gejala Masalah (Symptoms)
*   Gateway `10.88.8.18` dapat dijangkau via jaringan (`ping` berhasil dengan latency rata-rata 6.2ms dan 0% packet loss).
*   Port Modbus TCP `502` pada gateway berstatus **OPEN** (koneksi TCP berhasil terbentuk).
*   Poller daemon berjalan normal dan Laravel API siap menerima data.
*   Tidak ada data telemetri Modbus yang berhasil dibaca dari kedua meter.
*   Pesan kesalahan pada log poller (`/var/log/poller-slave17.log`):
    ```
    pymodbus.exceptions.ModbusIOException: Modbus Error: [Input/Output] No response received after 3 retries, continue with next request
    ```

---

## 3. Investigasi yang Dilakukan
Untuk mendiagnosis masalah, dilakukan serangkaian pengujian terisolasi sebagai berikut:
1.  **Verifikasi Berkas `.env`**: Memastikan `DEVICE_TOKEN`, `MODBUS_IP`, `MODBUS_PORT`, dan `MODBUS_SLAVE_ID` di server produksi telah dikonfigurasi dengan benar sesuai data di database.
2.  **Verifikasi Autentikasi API**: Memastikan header `X-Device-Token` pada request poller valid dan diterima oleh middleware Laravel.
3.  **Audit Konfigurasi Gateway**: Membaca parameter USR TCP Gateway `10.88.8.18` via panel admin web (`http://10.88.8.18/`):
    *   Baudrate diset `9600`, Parity `None` (Sesuai spesifikasi meter).
    *   Modbus Type diset `Modbus TCP/RTU` (Konversi protokol aktif).
    *   Similar RFC2217 diset `OFF` (Nonaktif).
4.  **Uji Pembacaan Register Mandiri**: Menggunakan skrip Python diagnostik interaktif untuk mencoba membaca register `3059` (Active Power) langsung ke Slave ID `17` dan `18`. Hasil pengujian tetap mengembalikan *ModbusIOException (No Response)*.
5.  **Pemindaian Slave ID (Modbus Scan)**: Menjalankan pemindaian serial secara sekuensial dari Slave ID 1 hingga 30 untuk mendeteksi apakah ada alamat fisik lain yang merespons. Hasil pemindaian menunjukkan tidak ada satu pun Slave ID yang memberikan jawaban.

---

## 4. Temuan Penting (Key Findings)
Setelah memeriksa halaman status internal gateway (`http://10.88.8.18/initialen.shtml`) saat kueri Modbus dikirimkan oleh server, didapatkan data statistik berikut:
*   **RX Counter** (penerimaan data net/TCP dari server): Mengalami peningkatan jumlah bytes (membuktikan gateway sukses menerima kueri dari server).
*   **TX Counter** (pengiriman data balik/response dari serial RS485): **Tetap bernilai 0**.
*   **Analisis**: Gateway berhasil meneruskan paket permintaan ke jalur serial RS485, namun tidak ada tanggapan sama sekali dari jaringan fisik RS485 (Power Meter).

---

## 5. Analisis Penyebab Utama (Root Cause)
Penyebab utama kegagalan komunikasi adalah **kesalahan fisik pada topologi instalasi kabel RS485 (Wiring Error)**.

Tim lapangan melakukan instalasi kabel komunikasi RS485 menggunakan pola **Paralel / Star Topology** (percabangan bintang), di mana kabel dari gateway dicabangkan langsung ke PM-17 dan PM-18 secara paralel menggunakan kabel biru-putih dan hijau-putih secara bersamaan.

Berdasarkan standar fisik RS485, penggunaan Star Topology atau percabangan paralel tanpa terminasi yang tepat menyebabkan refleksi sinyal (impedance mismatch) yang parah, sehingga data mengalami korupsi fisik di kabel dan meter tidak dapat mendeteksi atau merespons paket data dari gateway.

---

## 6. Tindakan Korektif (Corrective Action)
1.  **Rekonstruksi Jalur Kabel**: Tim lapangan membongkar percabangan star dan mengubah topologi wiring fisik menjadi **Daisy Chain** (seri berantai dari Gateway $\rightarrow$ PM-17 $\rightarrow$ PM-18).
2.  **Pemasangan Resistor Terminasi**: Memastikan resistor terminasi 120 Ohm terpasang pada ujung rantai daisy chain (meter terakhir) untuk menstabilkan sinyal.
3.  **Pengujian Ulang Perangkat**: Menjalankan kueri Modbus manual ke masing-masing meter setelah perbaikan wiring:
    *   **PM-17** memberikan respons sukses:
        ```python
        ReadHoldingRegistersResponse(dev_id=17) # Berhasil membaca register energi & daya
        ```
    *   **PM-18** memberikan respons sukses:
        ```python
        ReadHoldingRegistersResponse(dev_id=18) # Berhasil membaca register energi & daya
        ```

---

## 7. Pembelajaran (Lessons Learned)
*   **Topologi RS485 Mutlak**: Semua instalasi komunikasi serial untuk *Energy Tracker* wajib menggunakan topologi **Daisy Chain**.
*   **Hindari Star Topology**: Percabangan star atau paralel langsung sangat tidak direkomendasikan pada RS485 karena rentan terhadap interferensi elektromagnetik dan refleksi sinyal, meskipun secara logika kelistrikan paralel terlihat menyambung.
*   **Validasi Hardware Terlebih Dahulu**: Selalu lakukan pengujian pembacaan register Modbus secara manual menggunakan skrip diagnostik sebelum menduga adanya kerusakan pada software poller atau backend database.

---

## 8. Standar Baru Penanganan RS485
Mulai 20 Juni 2026, aturan berikut diberlakukan untuk semua pemasangan jaringan telemetri baru:
1.  Kabel RS485 wajib ditarik dalam satu jalur lurus (Daisy Chain) dari gateway ke meter pertama, dilanjutkan ke meter berikutnya, tanpa percabangan.
2.  Panjang maksimal stub (percabangan dari jalur utama ke meter) tidak boleh melebihi 30 cm.
3.  Uji *dry-run* menggunakan skrip pembacaan Modbus wajib disertakan dalam laporan commissioning sebelum service poller systemd dijalankan di server produksi.

---

## 9. Tindakan Pencegahan (Preventive Action)
*   Menambahkan checklist khusus mengenai struktur fisik kabel RS485 (Daisy Chain verification) ke dalam **SOP Commissioning Power Meter** (`.agent/workflows/power_meter_commissioning_guide.md`).
*   Melakukan audit visual terhadap jalur kabel serial pada gateway produksi lainnya untuk memastikan kepatuhan terhadap standar Daisy Chain guna mencegah degradasi sinyal di masa depan.
