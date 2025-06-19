<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $period = $_GET['period'] ?? 'day';
    
    switch($period) {
        case 'day':
            $where_clause = "DATE(t.date) = CURRENT_DATE";
            $label = "Daily";
            break;
        case 'week':
            $where_clause = "YEARWEEK(t.date) = YEARWEEK(CURRENT_DATE)";
            $label = "Weekly";
            break;
        case 'month':
            $where_clause = "DATE_FORMAT(t.date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')";
            $label = "Monthly";
            break;
        case 'year':
            $where_clause = "YEAR(t.date) = YEAR(CURRENT_DATE)";
            $label = "Yearly";
            break;
    }

    // Debug: Log period dan where clause
    error_log("Selected period: $period");
    error_log("Where clause: $where_clause");

    // Perbaikan query statistik
    $query = "SELECT 
        -- Total balance (sisa uang dari semua transaksi)
        (SELECT COALESCE(SUM(
            CASE 
                WHEN type = 'income' AND status = 'completed' THEN amount 
                WHEN type = 'expense' AND status = 'completed' THEN -amount
                ELSE 0
            END), 0)
        FROM transactions) as total_balance,
        
        -- Period stats
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t
         WHERE type = 'income' 
         AND status = 'completed'
         AND $where_clause) as period_income,
        
        (SELECT MAX(created_at) 
         FROM transactions t
         WHERE type = 'income' 
         AND (status = 'completed' OR status = 'used_for_goal')
         AND $where_clause) as last_income_update,
        
        -- Expense untuk period yang dipilih + last update
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t
         WHERE type = 'expense' 
         AND (status = 'completed' OR status = 'used_for_goal')
         AND $where_clause) as period_expenses,
         
        (SELECT MAX(created_at) 
         FROM transactions t
         WHERE type = 'expense' 
         AND (status = 'completed' OR status = 'used_for_goal')
         AND $where_clause) as last_expense_update,

        -- Savings untuk period yang dipilih
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t
         WHERE type = 'expense' 
         AND (status = 'completed' OR status = 'used_for_goal')
         AND expense_category_id IN (
             SELECT id FROM expense_categories 
             WHERE category_name = 'Savings'
         )
         AND $where_clause) as period_savings,

        -- Latest transaction time
        (SELECT MAX(created_at) FROM transactions 
         WHERE status IN ('completed', 'used_for_goal')) as last_update";

    $stmt = $conn->query($query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Log query results
    error_log("Query results: " . json_encode($stats));

    // Hitung savings rate
    $savingsRate = 0;
    if ($stats['period_income'] > 0) {
        $savingsRate = ($stats['period_savings'] / $stats['period_income']) * 100;
    }

    echo json_encode([
        'success' => true,
        'label' => $label,
        'total_balance' => floatval($stats['total_balance']),
        'period_income' => floatval($stats['period_income']),
        'period_expenses' => floatval($stats['period_expenses']),
        'savings_amount' => floatval($stats['period_savings']),
        'savings_rate' => round($savingsRate, 1),
        'last_update' => $stats['last_update'],
        'last_income_update' => $stats['last_income_update'],
        'last_expense_update' => $stats['last_expense_update']
    ]);

} catch(Exception $e) {
    error_log("Error in dashboard_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
