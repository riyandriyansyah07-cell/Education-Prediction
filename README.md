# Cegah Sebelum Terlambat

Sistem peringatan dini akademik berbasis AI untuk mahasiswa **FTII UHAMKA** (`https://peringatandini.online`). Capstone Project Kelompok 12.

Stack: **Laravel 13**, **PHP 8.4+**, **Tailwind CSS 4 (Vite)**, **MySQL / PostgreSQL** (SQLite untuk pengujian), serta layanan model Python (**FastAPI, scikit-learn, XGBoost, SHAP**) di folder `ml-service`.

## Fitur Utama

- **4 Peran Terisolasi (`EnsureRole`)**:
  - **Mahasiswa**: impor KHS (PDF resmi UHAMKA, XLSX, CSV), pemantauan skor risiko 0–10, kalkulator **Target UAS** tertinggi yang bisa diraih, riwayat semester + grafik IPS, pelacak **20 SKS MK Pengayaan** (termasuk deteksi otomatis singkatan nama KHS), **4 Syarat Wajib SKPI** beserta unggah bukti sertifikat privat (maks. 2 MB), catatan bimbingan DPA, serta pengingat risiko.
  - **DPA (Dosen Pembimbing Akademik)**: akses otomatis khusus mahasiswa bimbingannya (terisolasi 403 dari DPA lain), detail faktor risiko & target UAS, pencatatan + riwayat bimbingan, serta verifikasi bukti SKPI.
  - **Admin Sekretariat**: dasbor ringkasan fakultas (filter Prodi, Angkatan, DPA, Risiko + ekspor CSV), kelola akun & peran, antrean **Tiket Pengajuan Akun (E-Ticket)** dengan tautan atur sandi 24 jam, **Verifikasi SKPI**, unggah data historis anonim, pelatihan/aktivasi model ML, serta audit **Catatan akses**.
  - **Validator AI**: audit kesesuaian penjelasan AI Gemini terhadap perhitungan matematis + panduan koreksi dan ekspor laporan akurasi CSV.
- **Bobot Resmi FTII UHAMKA**:
  - **Komponen Mata Kuliah (KHS)**: UAS **40%** · UTS **30%** · Tugas **20%** · Keaktifan/Kehadiran **10%** (ambang huruf: A ≥ 80, B ≥ 68, C ≥ 56, D ≥ 45; hadir < 75% pada kelas reguler memicu peringatan minimal 3,0).
  - **Skor Risiko Mahasiswa (0–10)**: **75%** Nilai & Absensi Semester Berjalan + **25%** Riwayat Semester Lalu (50:50 di awal semester), ditambah indikator aditif TA Sem 7+ (`+0–1,0`) serta Progres 20 SKS Pengayaan & 4 Syarat SKPI Sem 5+ (`+0–2,0`).
- **Notifikasi Luar Website**:
  - **Pop-up Browser / OS** (Desktop & Android via Service Worker `/sw.js` dan polling `/notifikasi/cek`) untuk antrean E-Ticket, bukti SKPI, dan pengingat risiko.
  - **Email Instan ke Admin** (`AdminNewTicketMail`) setiap kali E-Ticket baru masuk, tetap diterima meski browser ditutup.

## Menjalankan Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Isi koneksi database dan `PSEUDONYM_SALT` (string acak 32+ karakter) di `.env` **sebelum** migrasi. Jangan mengubah `PSEUDONYM_SALT` setelah data nyata masuk karena mengubahnya memutus kecocokan `student_ref` (HMAC-SHA256 dari NIM).

```bash
php artisan migrate --seed
npm install
npm run build        # atau npm run dev saat mengembangkan
php artisan serve
```

## Peta Modul Utama

| Proses / Modul | Halaman | Kode Utama |
|---|---|---|
| Autentikasi, E-Ticket & Hak Akses | Masuk, Ajukan E-Ticket, Akun dan peran | `AuthController`, `AccountTicketController`, `EnsureRole`, `Admin\AccountController` |
| Impor & Validasi KHS | Impor data | `ImportService`, `SheetValidator`, `SheetReader` |
| Skor Risiko, Target UAS & AI | Ringkasan risiko, Metodologi | `RiskService`, `RiskModel`, `LlmAdvisor`, `ml-service/risk_ml.py` |
| 20 SKS Pengayaan & 4 Syarat SKPI | Pengayaan & SKPI, Verifikasi SKPI | `EnrichmentCatalog`, `Student\EnrichmentController`, `SkpiDocumentController` |
| Pendampingan DPA & Privasi | Mahasiswa bimbingan, Riwayat bimbingan, Privasi & pengingat | `Dpa\GuidanceController`, `Student\PrivacyController` |
| Notifikasi & Audit Akses | Sidebar Notifikasi, Catatan akses, Audit AI | `NotificationPollController`, `AccessLogController`, `Validator\AuditController` |
| Data Historis & Model ML | Data historis, Model prediksi | `Admin\HistoricalDataController`, `ModelService`, `ThresholdTrainer` |

## Pengujian

```bash
php artisan test                    # Unit & Feature tests Laravel
cd ml-service && python -m pytest   # Pengujian layanan ML Python
```

## Catatan Keamanan & Privasi (Zero-PII)

- Hak akses diverifikasi di server pada setiap permintaan (`EnsureRole` dan pemeriksaan kepemilikan DPA/mahasiswa).
- Data akademik, hasil risiko, dan layanan ML/LLM hanya menggunakan `student_ref` (HMAC-SHA256) tanpa mengirim Nama atau NIM.
- Berkas impor KHS dibaca dari memori/temp lalu langsung dihapus (tidak disimpan di server); bukti sertifikat SKPI disimpan di disk `local` privat dengan kontrol akses pemilik, DPA pembimbing, dan Admin.
- Login dibatasi 5 percobaan per menit, sesi kedaluwarsa 30 menit, dan persetujuan E-Ticket mengirim tautan pengaturan kata sandi sekali pakai (berlaku 24 jam) tanpa password plaintext.
