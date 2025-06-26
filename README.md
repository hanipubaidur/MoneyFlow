# 💰 MoneyFlow - Personal Finance Tracker

![Last Commit](https://img.shields.io/github/last-commit/hanipubaidur/MoneyFlow?style=flat-square)

## 📝 Latest Updates
- 🟢 "Account" column (source/destination) now shown in all transactions & exports
- 🟣 Category, income source, and account display is cleaner & inline editable
- 🟡 Savings target validation: input exceeding target only fills the gap
- 🔵 Excel Export: complete transaction data (account, description, etc) & auto summary
- 🟠 Dashboard: cashflow breakdown, expense, and more informative status badges
- 🟤 Soft/hard delete for categories & accounts based on usage
- 🟤 **Category cannot be hard deleted if still used in monthly summaries; will be soft deleted instead**
- 🟤 Improved form validation for transactions, savings, and progress bar animation
- 🟢 Responsive, cleaner UI for tables & lists (categories, accounts, transactions, etc)
- 🟣 **Report breakdown tables now show empty message and always display total in footer**

<div align="center">
  
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?style=for-the-badge&logo=php)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue?style=for-the-badge&logo=mysql)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-blueviolet?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow?style=for-the-badge&logo=javascript)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

</div>

## 👨‍💻 Author

<div align="center">
  <a href="https://github.com/hanipubaidur">
    <img src="https://avatars.githubusercontent.com/hanipubaidur" width="100px" style="border-radius:50%"/>
  </a>
  <h3>Hanif Ubaidur Rohman Syah</h3>
  <p>Full Stack Developer | UI/UX Design</p>
  
  [![GitHub](https://img.shields.io/badge/GitHub-hanipubaidur-181717?style=flat&logo=github)](https://github.com/hanipubaidur)
</div>

## 🌟 About MoneyFlow

MoneyFlow is a simple yet powerful app to record and analyze your personal finances.  
Track your income, expenses, savings targets, and account balances with ease.

---

## 🚀 Main Features

- **Dashboard Overview:**  
  See your balance, income, expenses, and ratios in real-time with animated breakdowns by period (daily, weekly, monthly, yearly).

- **Transaction Recording:**  
  Log income (cash, transfer, e-wallet) and expenses (categories, savings, etc) with easy selection.

- **Account Management:**  
  Add/edit/deactivate accounts (bank, e-wallet, cash) and manage all your money sources.

- **Dynamic Categories:**  
  Add/remove income sources and expense categories as needed.

- **Reports & Analysis:**  
  Full financial reports, cashflow charts, category breakdowns, monthly analytics, and Excel export.

- **Responsive & User Friendly:**  
  Modern UI, works great on both mobile and desktop.

- **Data Security:**  
  All data is stored locally in your MySQL database.

---

## ✨ Other Features

- Export data to Excel
- Inline edit/delete for categories, sources, and accounts
- Animated progress bars and status badges

---

## 🛠️ Tech Stack

- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript ES6
- Bootstrap 5
- Chart.js
- PHPSpreadsheet
- BoxIcons

---

## ⚙️ How to Install & Run

### 1. **Requirements**
- **XAMPP** (Apache & MySQL, PHP 7.4+)
- **Git** (to clone the repo)
- **VSCode** (optional, for editing)

### 2. **Clone the Repository**
Open **CMD** or **Git Bash**:
```bash
git clone https://github.com/hanipubaidur/MoneyFlow.git
```

### 3. **Move Folder to XAMPP**
- Open **File Explorer** to `C:\Users\<yourname>\MoneyFlow`
- Press `Ctrl+X` on the `MoneyFlow` folder
- Go to `C:\xampp\htdocs\`
- Press `Ctrl+V` to paste into `htdocs`

### 4. **Start XAMPP**
- Open the **XAMPP** application
- Start **Apache** and **MySQL**

### 5. **Open Project in VSCode**
- Open **VSCode**
- Press `Ctrl+K O` (Open Folder)
- Select the folder `C:\xampp\htdocs\MoneyFlow`

### 6. **Import Database**
- Open the file `database/money_flow.sql` in VSCode, `Ctrl+A` then `Ctrl+C`
- In XAMPP, click **Admin** on MySQL (phpMyAdmin)
- Create a new database, e.g. `money_flow`
- Go to the **SQL** menu, paste all contents of `money_flow.sql`, then click **Go**
- **OR:**  
  Go to the **Import** tab, select the `money_flow.sql` file from your project folder, then click **Go**

### 7. **Configure Database (Optional)**
- If needed, edit `config/database.php` to match your MySQL user/password

### 8. **Run in Browser**
- Open your browser and go to:  
  ```
  http://localhost/MoneyFlow
  ```

---

## 🧩 Function Descriptions

> **Note:**  
> Function descriptions and explanations are available as comments in each source file (such as `main.js`, `report.js`, `transactions.js`, etc).  
> Please do not remove these comments, so other developers can easily understand the code flow and purpose of each function.

---

<div align="center">
  Made with ❤️ by <a href="https://github.com/hanipubaidur">Hanif Ubaidur Rohman Syah</a>
  <br>
  © 2025 MoneyFlow
</div>