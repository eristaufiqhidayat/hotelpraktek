# Hotel Praktik Widuri

Sistem hotel (Property Management System) untuk praktik siswa Perhotelan dan Kuliner **SMK Keluarga Widuri**, dibangun dengan **Laravel 12** mengikuti mockup *PMS-SMK KELUARGA WIDURI*.

Siswa mengoperasikan hotel dengan alur kerja sungguhan, dari reservasi sampai night audit. Setiap transaksi (benar maupun yang ditolak sistem) tercatat, sehingga guru bisa memantau dan memberi nilai.

## Fitur

| Modul | Isi |
|---|---|
| **Masuk** | Siswa memilih nama dan bagian tugas (Front Office, Housekeeping, F&B / Kasir, Night Auditor). Guru masuk dengan PIN. |
| **Beranda** | Okupansi, kedatangan, keberangkatan, kamar siap jual, pendapatan resto, ringkasan status kamar. |
| **Reservasi** | Daftar reservasi (tab aktif, hari ini, mendatang, selesai/batal), pencarian, reservasi baru dengan cek ketersediaan, pembatalan, tabel ketersediaan 7 hari. |
| **Front Office** | Check-in (wajib no. identitas, kamar harus VC, deposit, kartu kunci), tamu walk-in, folio (posting laundry/minibar/telepon/pembayaran), cetak folio, check-out (saldo harus lunas atau deposit dikembalikan tepat, city ledger, early departure). |
| **Housekeeping** | Denah kamar per lantai dengan status VC/VD/OC/OD/OOO, filter status, ubah status, tugaskan room attendant, laporan kerusakan / Out of Order. |
| **Restoran (POS)** | Menu per kategori, keranjang, pajak & layanan, bayar tunai/QRIS/kartu atau bebankan ke kamar tamu yang menginap, struk. |
| **Night Audit** | Cek kedatangan (jadi no-show) dan keberangkatan yang belum selesai, posting tarif kamar + pajak, OC → OD, tanggal hotel maju, laporan okupansi/ADR/RevPAR. |
| **Laporan** | Okupansi, ADR, RevPAR, pendapatan kamar/F&B/lainnya 7, 14, atau 30 hari, grafik harian, cetak. |
| **Panel Guru** | Rekap benar/salah per siswa, ketepatan dan saran nilai, log aktivitas dengan filter, unduh log CSV, kirim skenario latihan (rombongan walk-in, tamu VIP, komplain, overbooking, sarapan rombongan), reset hotel ke data awal. |
| **Pengaturan** | Nama hotel, kelas, pajak & layanan, tarif per tipe kamar, PIN guru, daftar siswa. |

Data contoh: 30 kamar di 3 lantai (Standard Double, Superior Twin, Deluxe King, Junior Suite), 13 tamu menginap, kedatangan hari ini, reservasi mendatang, riwayat 2 minggu untuk laporan, dan log aktivitas kelas kemarin.

## Kebutuhan

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_sqlite` (atau `pdo_mysql`), `mbstring`, `intl`, `xml`
- Composer 2
- Tidak perlu Node.js (CSS dan JS sudah ada di `public/`)

## Instalasi

```bash
git clone https://github.com/eristaufiqhidayat/hotelpraktek.git
cd hotelpraktek
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Atau jalankan sekaligus: `composer run setup`, lalu `php artisan serve`.

### Memakai MySQL

Ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotelpraktek
DB_USERNAME=root
DB_PASSWORD=
```

lalu `php artisan migrate --seed`.

## Masuk

- **Siswa**: pilih nama dan bagian tugas, tanpa kata sandi (mode latihan).
- **Guru**: PIN awal `widuri123` (diatur lewat `GURU_PIN` di `.env` sebelum seeding). Segera ganti PIN di menu **Pengaturan**.

## Mengulang latihan

- Dari aplikasi: **Panel Guru → Reset hotel ke data awal** (nama hotel, kelas, dan PIN guru tetap).
- Dari terminal: `php artisan migrate:fresh --seed`.

Tanggal hotel dimulai dari tanggal hari ini saat seeding, lalu maju setiap night audit dijalankan.

## Aturan yang dicek sistem

Transaksi yang melanggar aturan ditolak dan dicatat sebagai **Salah** di log guru, misalnya:

- check-in tanpa nomor identitas, ke kamar kotor (VD), rusak (OOO), atau yang sedang ditempati
- check-in untuk reservasi yang bukan tanggal hari ini
- reservasi saat tipe kamar penuh, atau tanggal datang sebelum tanggal hotel
- check-out saat saldo belum lunas, jumlah bayar/refund tidak tepat, atau kartu kunci belum kembali
- pembayaran di folio melebihi saldo
- POS dibebankan ke kamar yang tidak ditempati
- night audit saat masih ada tamu yang harus check-out

## Struktur kode

```
app/Services/Hotel.php        aturan inti: tanggal hotel, ketersediaan, statistik, log
app/Services/DemoData.php     data contoh (dipakai seeder dan tombol reset)
app/Http/Controllers/         satu controller per modul
app/View/ModalComposer.php    data untuk modal (?modal=checkin&id=...)
resources/views/              tampilan Blade sesuai mockup
public/css/app.css            gaya dari mockup
public/js/app.js              interaksi kecil (modal, konfirmasi, POS tanpa reload)
tests/Feature/HotelFlowTest.php
```

Jalankan pengujian: `php artisan test`.

---

Disiapkan oleh Peacock Integrasi Indonesia untuk SMK Keluarga Widuri. Semua data tamu adalah contoh.
