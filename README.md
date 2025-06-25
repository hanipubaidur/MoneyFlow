# 💰 MoneyFlow - Pencatat Keuangan Pribadi

![Last Commit](https://img.shields.io/github/last-commit/hanipubaidur/MoneyFlow?style=flat-square)

## 📝 Pembaruan Terbaru
- 🟢 Kolom "Account" (asal/tujuan uang) sekarang tampil di semua transaksi & export
- 🟣 Tampilan kategori, sumber income, dan akun kini lebih rapi & bisa edit langsung
- 🟡 Validasi savings target: input melebihi kekurangan target hanya masuk kekurangannya
- 🔵 Export Excel: data transaksi lengkap (account, keterangan, dsb) & summary otomatis
- 🟠 Dashboard: breakdown cashflow, expense, dan badge status lebih informatif
- 🟤 Soft/hard delete kategori & akun otomatis sesuai penggunaan
- 🟤 Perbaikan validasi form transaksi, savings, dan animasi progress bar
- 🟢 Responsive, UI tabel & list lebih rapih (kategori, akun, transaksi, dsb)
- 🟣 **Laporan: breakdown tabel income/expense kini menampilkan pesan jika data kosong dan total tetap muncul di footer**

<div align="center">
  
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?style=for-the-badge&logo=php)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue?style=for-the-badge&logo=mysql)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-blueviolet?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow?style=for-the-badge&logo=javascript)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

</div>

## 👨‍💻 Dibuat Oleh

<div align="center">
  <a href="https://github.com/hanipubaidur">
    <img src="https://avatars.githubusercontent.com/hanipubaidur" width="100px" style="border-radius:50%"/>
  </a>
  <h3>Hanif Ubaidur Rohman Syah</h3>
  <p>Full Stack Developer | UI/UX Design</p>
  
  [![GitHub](https://img.shields.io/badge/GitHub-hanipubaidur-181717?style=flat&logo=github)](https://github.com/hanipubaidur)
</div>

## 🌟 Tentang MoneyFlow

MoneyFlow dibuat untuk membantu mencatat dan menganalisa keuangan pribadi dengan mudah. Aplikasi ini lahir dari kesulitan mencari aplikasi pencatat keuangan yang simpel namun tetap informatif.

### ✨ Fitur Utama
- 📊 Dashboard keuangan realtime (periode harian, mingguan, bulanan, tahunan)
- 💰 Catat pemasukan & pengeluaran, pilih sumber/kategori & akun (bank, e-wallet, cash)
- 🏦 Manajemen akun (bank, e-wallet, cash) & bisa edit/hapus
- 🎯 Target & goals keuangan dengan validasi cerdas
- 📈 Analisis cashflow & breakdown kategori, badge status otomatis
- 💾 Export laporan Excel (data lengkap: account, keterangan, dsb)
- 📝 Edit/hapus kategori, sumber income, dan akun langsung dari halaman kategori
- 📱 Tampilan responsif & tabel/list lebih rapih
- 🔄 Soft/hard delete kategori & akun otomatis sesuai penggunaan
- 🟣 **Breakdown laporan income/expense tampilkan pesan jika data kosong & total tetap muncul di footer**

## 🛠️ Teknologi yang Digunakan
- PHP 7.4+
- MySQL 5.7+ 
- HTML5, CSS3, JavaScript ES6
- Bootstrap 5
- Chart.js
- PHPSpreadsheet 
- BoxIcons

## ⚙️ Cara Install

1. **Clone Repository**
```bash
git clone https://github.com/hanipubaidur/MoneyFlow.git
cd MoneyFlow
```

2. **Setup Database**
```bash
# Import database
mysql -u root -p < database/money_flow.sql

# Copy & edit konfigurasi
cp config/database.example.php config/database.php
```

3. **Install Dependencies**
```bash
composer install
```

## 📱 Cara Penggunaan

### Dashboard
- Lihat ringkasan keuangan
- Pantau cashflow harian/mingguan/bulanan/tahunan
- Analisis pengeluaran per kategori & akun

### Transaksi
- Catat pemasukan/pengeluaran
- Pilih kategori, sumber, dan akun (bank/e-wallet/cash)
- Input nominal & keterangan

### Target Keuangan 
- Buat target tabungan
- Set target nominal
- Monitor progres (otomatis validasi jika input melebihi target)

### Kategori, Sumber, Akun
- Tambah/edit/hapus kategori pengeluaran, sumber income, dan akun
- Semua list tampil rapi, bisa edit langsung tanpa reload halaman

### Laporan
- Pilih periode laporan
- Export ke Excel (data lengkap, summary, badge status)
- Lihat grafik analisis & badge status per bulan

##

<div align="center">
  Dibuat dengan ❤️ oleh <a href="https://github.com/hanipubaidur">Hanif Ubaidur Rohman Syah</a>
  <br>
  © 2025 MoneyFlow
</div>