# Laporan Progres Pengembangan ALKES SBS

**Tanggal:** 13 September 2026  
**Domain Development:** https://sbs.projectskuy.site  
**Repository:** https://github.com/muhammadroyyan11/alkes-sbs-fullstack  
**Status Fase 2:** Berjalan — 75%

## Ringkasan

Domain development ALKES SBS sudah aktif. Sistem utama admin, katalog customer, keranjang, checkout dasar, akun customer, alamat, dan riwayat pesanan sudah dapat digunakan. Tampilan storefront juga telah disesuaikan dengan identitas toko menggunakan tema putih dan hijau olive.

## Pekerjaan yang Sudah Selesai

### Infrastruktur dan Backend

- Laravel 12, MySQL 8, PHP-FPM, dan Nginx berjalan menggunakan Docker.
- Autentikasi customer dan admin tersedia.
- Role dan permission menggunakan Spatie Laravel Permission.
- Database dan sample data produk, varian, stok, supplier, purchase order, serta purchase receive tersedia.
- Limit request API router dinaikkan menjadi 100 MB dan telah diverifikasi.

### Admin Panel

- Dashboard admin dan statistik dasar.
- CRUD user, produk, varian, supplier, dan website settings.
- Manajemen stok dan mutasi stok.
- Stock opname beserta proses count, approve, dan reject.
- Purchase order dan purchase receive.
- Sidebar menu berbasis database dan akses role.

### Website Customer

- Homepage dan katalog produk.
- Pencarian berdasarkan nama, SKU, dan deskripsi.
- Filter stok dan pengurutan produk.
- Detail produk dan pemilihan varian.
- Keranjang berbasis session.
- Checkout customer dengan validasi alamat dan stok.
- Pilihan pengiriman reguler dan instant dengan ongkir sementara.
- Pembuatan order, order item, shipment, serta pengurangan stok otomatis.
- Riwayat dan detail pesanan customer.
- Profil customer dan pengelolaan alamat.

### Penyempurnaan UI/UX

- Tema merah storefront diganti menjadi putih dan hijau olive sesuai interior toko.
- Warna navbar, hero, tombol, katalog, CTA, footer, serta scrollbar diselaraskan.
- Katalog mobile menampilkan dua kartu produk per baris.
- Halaman akun diubah menjadi dashboard bergaya marketplace.
- Dashboard akun menampilkan kartu profil, ringkasan akun, status pesanan, transaksi terakhir, alamat utama, edit profil, dan daftar alamat.
- Status pesanan dapat membuka daftar transaksi yang sudah terfilter: belum dibayar, diproses, dikirim, dan selesai.

### Quality Assurance

- Syntax PHP dan kompilasi Blade berhasil.
- Feature test storefront: 5 test berhasil dengan 35 assertions.
- Seluruh test suite: 39 test berhasil dengan 214 assertions.
- Tampilan dashboard akun telah diperiksa pada viewport desktop dan mobile.

## Pekerjaan Fase 2 yang Masih Tersisa

- Manajemen order customer dari admin: proses order, perubahan status, konfirmasi pembayaran, dan input nomor resi.
- Integrasi Midtrans Snap dan callback pembayaran.
- Perhitungan ongkir langsung dari RajaOngkir dan GoSend.
- Tracking pengiriman dan sinkronisasi status kurir.
- Widget Tawk.to live chat.
- Notifikasi perubahan status pesanan melalui email atau WhatsApp.

## Rekomendasi Pekerjaan Berikutnya

1. Membuat manajemen order dan shipment pada admin panel.
2. Menyelesaikan Midtrans dalam mode sandbox sampai callback terverifikasi.
3. Mengintegrasikan ongkir RajaOngkir dan GoSend.
4. Menambahkan nomor resi, timeline pengiriman, dan notifikasi customer.
5. Memasang Tawk.to setelah alur transaksi utama stabil.
