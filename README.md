# 💰 MoneyFlow - Personal Finance Tracker

![Last Commit](https://img.shields.io/github/last-commit/hanipubaidur/MoneyFlow?style=flat-square)

## 📝 Latest Updates
- 🟢 **[BUGFIX] Transaction history now correctly displays all transactions after editing any transaction (no more missing transactions after edit)**

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
  See your balance, income, expenses, and ratios in real-time with animated breakdowns by period (daily, weekly, monthly, yearly) **or by custom date**.
  - **If there are no transactions in the selected period, income/expense will always show 0.**
  - **Weekly stats and charts use date-based weeks (Week 1 = tgl 1-7, Week 2 = tgl 8-14, etc).**

- **Transaction Recording:**  
  Log income (cash, transfer, e-wallet) and expenses (categories, savings, etc) with easy selection.
  - **Transfer transactions are never counted as income or expense in any chart/stat.**

- **Account Management:**  
  Add/edit/deactivate accounts (bank, e-wallet, cash) and manage all your money sources.

- **Dynamic Categories:**  
  Add/remove income sources and expense categories as needed.
  - **Custom color for each expense category, shown in expense charts**

- **Reports & Analysis:**  
  Full financial reports, cashflow charts, category breakdowns, monthly analytics, and Excel export.  
  **Select any date to see report and analytics for that day. If no data, income/expense will show 0.**

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