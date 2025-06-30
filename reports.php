<?php
require_once 'config/database.php';

function getPeriodLabelForAjax($period, $date = null) {
    $date = $date ?: date('Y-m-d');
    switch($period) {
        case 'day': return "Today, " . date('d M Y', strtotime($date));
        case 'week': return "This Week";
        case 'month': return "This Month (" . date('F Y', strtotime($date)) . ")";
        case 'year': return "This Year (" . date('Y', strtotime($date)) . ")";
        case 'date': 
            $d = date('d/m/Y', strtotime($date));
            return "Selected Date: " . $d;
        default: return '';
    }
}

// --- AJAX HANDLER ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $period = $_GET['period'] ?? 'month';
    $selectedDate = $_GET['date'] ?? null;
    $db = new Database();
    $conn = $db->getConnection();
    $where_clause = "";
    switch($period) {
        case 'day': $where_clause = "DATE(t.date) = CURRENT_DATE"; break;
        case 'week': $where_clause = "YEARWEEK(t.date, 1) = YEARWEEK(CURRENT_DATE, 1)"; break;
        case 'month': $where_clause = "DATE_FORMAT(t.date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')"; break;
        case 'year': $where_clause = "YEAR(t.date) = YEAR(CURRENT_DATE)"; break;
        case 'date':
            $date = $selectedDate ?: date('Y-m-d');
            $where_clause = "DATE(t.date) = " . $conn->quote($date);
            break;
        default: $where_clause = "DATE_FORMAT(t.date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')";
    }
    
    // Metrik Utama per periode
    $metricsQuery = "SELECT 
        COALESCE(SUM(CASE WHEN t.type = 'income' AND NOT (t.income_source_id IS NULL AND t.expense_category_id IS NULL) THEN t.amount ELSE 0 END), 0) as total_income, 
        COALESCE(SUM(CASE WHEN t.type = 'expense' AND NOT (t.income_source_id IS NULL AND t.expense_category_id IS NULL) THEN t.amount ELSE 0 END), 0) as total_expense, 
        COALESCE(SUM(CASE WHEN t.expense_category_id = (SELECT id FROM expense_categories WHERE category_name = 'Savings') THEN t.amount ELSE 0 END), 0) as savings_amount, 
        COALESCE(SUM(CASE WHEN t.expense_category_id = (SELECT id FROM expense_categories WHERE category_name = 'Debt/Loan') THEN t.amount ELSE 0 END), 0) as debt_amount 
        FROM transactions t 
        WHERE $where_clause AND t.status = 'completed'";
    $metrics = $conn->query($metricsQuery)->fetch(PDO::FETCH_ASSOC);

    // Breakdown
    $incomeBreakdown = $conn->query("SELECT i.source_name, SUM(t.amount) as total FROM transactions t LEFT JOIN income_sources i ON t.income_source_id = i.id WHERE t.type = 'income' AND t.status = 'completed' AND $where_clause AND NOT (t.income_source_id IS NULL AND t.expense_category_id IS NULL) GROUP BY i.id ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    $expenseBreakdown = $conn->query("SELECT e.category_name, SUM(t.amount) as total FROM transactions t LEFT JOIN expense_categories e ON t.expense_category_id = e.id WHERE t.type = 'expense' AND t.status = 'completed' AND $where_clause AND NOT (t.income_source_id IS NULL AND t.expense_category_id IS NULL) GROUP BY e.id ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Data JSON sekarang tidak lagi berisi account_balances
    $data = [
        'periodLabel' => getPeriodLabelForAjax($period, $selectedDate),
        'metrics' => [
            'net_cashflow' => $metrics['total_income'] - $metrics['total_expense'],
            'savings_rate' => $metrics['total_income'] > 0 ? ($metrics['savings_amount'] / $metrics['total_income'] * 100) : 0,
            'expense_ratio' => $metrics['total_income'] > 0 ? ($metrics['total_expense'] / $metrics['total_income'] * 100) : 0,
            'debt_ratio' => $metrics['total_income'] > 0 ? ($metrics['debt_amount'] / $metrics['total_income'] * 100) : 0
        ],
        'breakdowns' => [
            'income' => $incomeBreakdown,
            'expense' => $expenseBreakdown,
            'totalIncome' => array_sum(array_column($incomeBreakdown, 'total')),
            'totalExpense' => array_sum(array_column($expenseBreakdown, 'total'))
        ]
    ];
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Bagian bawah (Initial Load)
$pageTitle = "Financial Reports";
$db = new Database();
$conn = $db->getConnection();
function formatCurrency($amount) {
    return 'Rp ' . number_format(floor($amount), 0, ',', '.');
}
// Query ini sekarang menghitung TOTAL SALDO KESELURUHAN (seperti semula)
$accountsQuery = "SELECT a.id, a.account_name, a.account_type, COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_in, COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_out FROM accounts a LEFT JOIN transactions t ON a.id = t.account_id AND t.status = 'completed' WHERE a.is_active = TRUE GROUP BY a.id ORDER BY a.account_type, a.account_name";
$accounts = $conn->query($accountsQuery)->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h3 class="mb-0">📊 Financial Report</h3>
        <div id="periodLabel" class="text-muted small">Loading...</div>
    </div>
    <div class="col-md-6 text-md-end">
        <div id="reportPeriodSelector" class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" data-period="day">Day</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-period="week">Week</button>
            <button type="button" class="btn btn-sm btn-outline-primary active" data-period="month">Month</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-period="year">Year</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectDateBtn">Select Date</button>
        </div>
    </div>
</div>
<!-- Modal Custom Date Picker -->
<div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customDateModalLabel">Select Date</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div class="d-flex justify-content-center align-items-center gap-2">
          <select id="customDateDay" class="form-select form-select-sm" style="width:auto"></select>
          <select id="customDateMonth" class="form-select form-select-sm" style="width:auto"></select>
          <select id="customDateYear" class="form-select form-select-sm" style="width:auto"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="showDateBtn">Show</button>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card bg-primary text-white h-100"><div class="card-body"><h6>Net Cash Flow</h6><h3 id="netCashflow" class="mb-0">Rp 0</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-success text-white h-100"><div class="card-body"><h6>Savings Rate</h6><h3 id="savingsRate" class="mb-0">0%</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-info text-white h-100"><div class="card-body"><h6>Expense Ratio</h6><h3 id="expenseRatio" class="mb-0">0%</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-warning text-white h-100"><div class="card-body"><h6>Debt Ratio</h6><h3 id="debtRatio" class="mb-0">0%</h3></div></div></div>
</div>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Account Balances (Overall)</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($accounts as $acc): 
                        $balance = $acc['total_in'] - $acc['total_out']; // Ini sekarang total balance
                        $color = 'secondary'; 
                        if ($acc['account_type'] === 'cash') $color = 'success'; 
                        elseif ($acc['account_type'] === 'bank') $color = 'primary'; 
                        elseif ($acc['account_type'] === 'e-wallet') $color = 'info'; 
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="card border-start border-4 border-<?= $color ?> shadow-sm h-100">
                            <div class="card-body py-3 px-2">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 ps-2">
                                        <div class="fw-bold"><?= htmlspecialchars($acc['account_name']) ?></div>
                                        <div class="small text-muted"><?= ucfirst($acc['account_type']) ?></div>
                                    </div>
                                    <div class="ms-2 text-<?= $color ?> fw-bold" style="font-size:1.1rem; width: 160px; text-align: right;">
                                        <span class="account-balance-anim" id="accountBalance<?= $acc['id'] ?>" data-balance="<?= floor($balance) ?>" style="font-variant-numeric: tabular-nums;"><?= formatCurrency(0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-6 mb-4 mb-md-0"><div class="card h-100"><div class="card-header bg-success text-white"><h6 class="mb-0">Income Breakdown</h6></div><div class="card-body p-2"><table class="table table-sm mb-0"><thead><tr><th>Source</th><th class="text-end">Amount</th><th class="text-end">%</th></tr></thead><tbody id="incomeBreakdownBody"></tbody><tfoot id="incomeBreakdownFoot" class="fw-bold"></tfoot></table></div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-header bg-danger text-white"><h6 class="mb-0">Expense Breakdown</h6></div><div class="card-body p-2"><table class="table table-sm mb-0"><thead><tr><th>Category</th><th class="text-end">Amount</th><th class="text-end">%</th></tr></thead><tbody id="expenseBreakdownBody"></tbody><tfoot id="expenseBreakdownFoot" class="fw-bold"></tfoot></table></div></div></div>
</div>
<?php
$pageContent = ob_get_clean();
$pageScript = '<script src="assets/js/reportCharts.js"></script>';
include 'includes/layout.php';
?>