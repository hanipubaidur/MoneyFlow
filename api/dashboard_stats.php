<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $period = $_GET['period'] ?? 'month';
    
    switch($period) {
        case 'day':
            $where_clause = "DATE(date) = CURRENT_DATE";
            $label = "Daily";
            break;
        case 'week':
            // Week by date: Week 1 = tgl 1-7, Week 2 = tgl 8-14, dst
            $today = date('j'); // tgl 1-31
            $weekNum = floor(($today - 1) / 7) + 1;
            $startDay = ($weekNum - 1) * 7 + 1;
            $endDay = min($startDay + 6, date('t'));
            $where_clause = "MONTH(date) = MONTH(CURRENT_DATE) AND YEAR(date) = YEAR(CURRENT_DATE) AND DAY(date) BETWEEN $startDay AND $endDay";
            $label = "Weekly";
            break;
        case 'month':
            $where_clause = "DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')";
            $label = "Monthly";
            break;
        case 'year':
            $where_clause = "YEAR(date) = YEAR(CURRENT_DATE)";
            $label = "Yearly";
            break;
        default:
            $where_clause = "DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')";
            $label = "Monthly";
    }

    // Perbaiki query untuk statistik yang lebih akurat
    $query = "SELECT 
        (SELECT total_balance FROM balance_tracking WHERE id = 1) as total_balance,
        
        (SELECT COALESCE(SUM(amount), 0)
         FROM transactions 
         WHERE type = 'income' AND status = 'completed'
         AND (income_source_id IS NOT NULL OR expense_category_id IS NOT NULL)
         AND $where_clause) as period_income,
         
        (SELECT COALESCE(SUM(amount), 0)
         FROM transactions 
         WHERE type = 'expense' AND status = 'completed'
         AND (income_source_id IS NOT NULL OR expense_category_id IS NOT NULL)
         AND $where_clause) as period_expenses,
         
        (SELECT COALESCE(SUM(t.amount), 0)
         FROM transactions t
         JOIN expense_categories ec ON t.expense_category_id = ec.id
         WHERE t.type = 'expense' AND t.status = 'completed'
         AND ec.category_name = 'Savings'
         AND $where_clause) as period_savings,
         
        (SELECT MAX(created_at) FROM transactions WHERE $where_clause) as last_update,
        
        (SELECT MAX(created_at) 
         FROM transactions 
         WHERE type = 'income' AND $where_clause) as last_income_update,
         
        (SELECT MAX(created_at)
         FROM transactions 
         WHERE type = 'expense' AND $where_clause) as last_expense_update";

    $stats = $conn->query($query)->fetch(PDO::FETCH_ASSOC);
    
    // Hitung savings rate
    $savingsRate = $stats['period_income'] > 0 ? 
        ($stats['period_savings'] / $stats['period_income'] * 100) : 0;

    echo json_encode([
        'success' => true,
        'label' => $label,
        'total_balance' => floatval($stats['total_balance']),
        'period_income' => floatval($stats['period_income']),
        'period_expenses' => floatval($stats['period_expenses']),
        'savings_amount' => floatval($stats['period_savings']),
        'savings_rate' => round($savingsRate, 1),
        // Kirim null jika tidak ada data
        'last_update' => $stats['last_update'] ?: null,
        'last_income_update' => $stats['last_income_update'] ?: null,
        'last_expense_update' => $stats['last_expense_update'] ?: null
    ]);

} catch(Exception $e) {
    error_log("Error in dashboard_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

