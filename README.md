# 💰 MoneyFlow - Pencatat Keuangan Pribadi
Update Terakhir: 19 Juni 2025 4:30pm

## 📝 Pembaruan Terbaru
- 🟢 Perbaikan logika badge status Surplus/Deficit/Break Even/No Data pada Monthly Comparison Table
- 🟣 Validasi savings target: jika input melebihi kekurangan target, hanya kekurangannya yang masuk ke target
- 🟡 Export Excel hanya menampilkan data yang ada di database, judul dan emoji tetap cantik
- 🔵 Perbaikan tampilan dan logika timestamp dashboard (tampil "No transactions yet" jika kosong)
- 🟠 Perbaikan query dan tampilan dashboard stats (income/expense/savings per periode)
- 🟤 Konsistensi format currency dan animasi progress bar
- ⚡ Optimasi query dan pengelolaan kategori (soft/hard delete)
- 🟤 Perbaikan validasi form transaksi dan savings
- 🟢 Penambahan dan perbaikan style badge, alignment, dan tabel

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
- 💰 Catat pemasukan & pengeluaran 
- 🎯 Target & goals keuangan dengan validasi cerdas
- 📈 Analisis cashflow & breakdown kategori
- 💾 Export laporan Excel (hanya data yang ada di database, judul & emoji tetap cantik)
- 📱 Tampilan responsif
- 🟢 Badge status Surplus/Deficit/Break Even/No Data pada tabel laporan
- 🟣 Validasi savings target otomatis
- 🔄 Soft/hard delete kategori otomatis sesuai penggunaan

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
- Analisis pengeluaran per kategori

### Transaksi
- Catat pemasukan/pengeluaran
- Pilih kategori transaksi 
- Input nominal & keterangan

### Target Keuangan 
- Buat target tabungan
- Set target nominal
- Monitor progres (otomatis validasi jika input melebihi target)

### Laporan
- Pilih periode laporan
- Export ke Excel (hanya data yang ada di database)
- Lihat grafik analisis & badge status per bulan

##

<div align="center">
  Dibuat dengan ❤️ oleh <a href="https://github.com/hanipubaidur">Hanif Ubaidur Rohman Syah</a>
  <br>
  © 2025 MoneyFlow
</div>