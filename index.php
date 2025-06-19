<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Add formatCurrency function before usage
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Get initial stats with current month
$statsQuery = "SELECT 
    bt.total_balance,
    bt.total_savings,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'income' AND status = 'completed'
     AND DATE(date) = CURRENT_DATE) as day_income,
     
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'expense' AND status = 'completed'
     AND DATE(date) = CURRENT_DATE) as day_expenses,
     
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'income' AND status = 'completed'
     AND YEARWEEK(date) = YEARWEEK(CURRENT_DATE)) as week_income,
     
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'expense' AND status = 'completed'
     AND YEARWEEK(date) = YEARWEEK(CURRENT_DATE)) as week_expenses,
     
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'income' AND status = 'completed'
     AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')) as month_income,
     
    (SELECT COALESCE(SUM(amount), 0) 
     FROM transactions 
     WHERE type = 'expense' AND status = 'completed'
     AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')) as month_expenses
    
FROM balance_tracking bt WHERE bt.id = 1";

$stats = $conn->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyFlow Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            transition: all 0.3s ease;
        }
        .chart-container {
            position: relative;
            min-height: 400px;
            margin: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <!-- Total Balance -->
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Total Balance</h6>
                        <h3 class="card-title mb-0" id="totalBalance">
                            <?= formatCurrency(0) ?>
                        </h3>
                        <small class="text-white-50" id="lastUpdate" data-time=""></small>
                    </div>
                </div>
            </div>

            <!-- Period Income -->
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2"><span class="periodLabel">Monthly</span> Income</h6>
                        <h3 class="card-title mb-0" id="periodIncome"></h3>
                        <small class="text-white-50" id="lastIncomeUpdate" data-time=""></small>
                    </div>
                </div>
            </div>

            <!-- Period Expenses -->
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2"><span class="periodLabel">Monthly</span> Expenses</h6>
                        <h3 class="card-title mb-0" id="periodExpenses"></h3>
                        <small class="text-white-50" id="lastExpenseUpdate" data-time=""></small>
                    </div>
                </div>
            </div>

            <!-- Savings Rate -->
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">Savings Rate</h6>
                        <h3 class="card-title mb-0" id="savingsRate">0%</h3>
                        <div class="progress mt-2" style="height: 15px;">
                            <div class="progress-bar" id="savingsProgress" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts & Analysis -->
        <div class="row mb-4">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Cash Flow Analysis</h5>
                        <div class="btn-group period-selector">
                            <button type="button" class="btn btn-outline-primary" data-period="day">Day</button>
                            <button type="button" class="btn btn-outline-primary" data-period="week">Week</button>
                            <button type="button" class="btn btn-outline-primary active" data-period="month">Month</button>
                            <button type="button" class="btn btn-outline-primary" data-period="year">Year</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 300px;">
                            <canvas id="cashFlowChart"></canvas>
                            <div id="chartLoading" class="text-center py-5" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Expense Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 250px;">
                            <canvas id="expenseDonutChart"></canvas>
                            <div id="donutLoading" class="text-center py-5" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody id="expenseBreakdownTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions & Goals -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Transactions</h5>
                        <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="recentTransactions">
                            <div id="transactionLoading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Savings Preview -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total Savings</h5>
                        <a href="savings.php" class="btn btn-sm btn-primary">View Details</a>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-3">
                            <h3 class="text-primary mb-0" id="totalSavings">
                                <?= formatCurrency($stats['total_savings']) ?>
                            </h3>
                            <small class="text-muted">Total Accumulated Savings</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        // Helper untuk update timestamp
        function timeAgo(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            
            if (seconds < 10) return 'just now';
            if (seconds < 60) return `${seconds} seconds ago`;
            
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
            
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
            
            const days = Math.floor(hours / 24);
            return `${days} day${days !== 1 ? 's' : ''} ago`;
        }

        function updateTimestamp() {
            const elements = [
                document.getElementById('lastUpdate'),
                document.getElementById('lastIncomeUpdate'),
                document.getElementById('lastExpenseUpdate')
            ];

            elements.forEach(el => {
                if (el && el.dataset.time) {
                    el.textContent = timeAgo(el.dataset.time);
                }
            });
        }

        // Update setiap detik
        setInterval(updateTimestamp, 1000);
        
        // Update pertama kali
        updateTimestamp();
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>