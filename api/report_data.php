<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $period = $_GET['period'] ?? 'day';
    $date = date('Y-m-d'); // Get current date for calculations

    // Perbaiki where clause untuk lebih akurat
    switch($period) {
        case 'day':
            $where_clause = "DATE(t.date) = DATE('$date')";
            break;
        case 'week':
            $where_clause = "t.date >= DATE_SUB('$date', INTERVAL WEEKDAY('$date') DAY) 
                           AND t.date <= DATE('$date')";
            break;
        case 'month':
            $where_clause = "YEAR(t.date) = YEAR('$date') AND MONTH(t.date) = MONTH('$date')";
            break;
        case 'year':
            $where_clause = "YEAR(t.date) = YEAR('$date')";
            break;
        default:
            $where_clause = "DATE(t.date) = DATE('$date')";
    }

    // Get metrics with updated query
    $metricsQuery = "SELECT 
        COALESCE(SUM(CASE WHEN t.type = 'income' AND t.status = 'completed' THEN t.amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN t.type = 'expense' AND t.status = 'completed' THEN t.amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE 
            WHEN t.type = 'expense' AND t.status = 'completed' 
            AND t.expense_category_id IN (SELECT id FROM expense_categories WHERE category_name = 'Savings')
            THEN t.amount ELSE 0 END), 0) as savings_amount,
        COALESCE(SUM(CASE 
            WHEN t.type = 'expense' AND t.status = 'completed'
            AND t.expense_category_id IN (SELECT id FROM expense_categories WHERE category_name = 'Debt/Loan')
            THEN amount ELSE 0 END), 0) as debt_amount,
        (SELECT COALESCE(SUM(CASE 
            WHEN type = 'income' AND status = 'completed' THEN amount 
            WHEN type = 'expense' AND status = 'completed' THEN -amount 
            WHEN status = 'deleted' THEN 0
            ELSE 0 END), 0)
        FROM transactions) as all_time_balance
    FROM transactions t
    WHERE $where_clause";

    $stmt = $conn->query($metricsQuery);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate derived metrics - perbaiki perhitungan
    $net_cashflow = $metrics['all_time_balance']; // Gunakan total balance keseluruhan
    $savings_rate = $metrics['total_income'] > 0 ? 
        ($metrics['savings_amount'] / $metrics['total_income'] * 100) : 0;
    $expense_ratio = $metrics['total_income'] > 0 ? 
        ($metrics['total_expense'] / $metrics['total_income'] * 100) : 0;
    $debt_ratio = $metrics['total_income'] > 0 ? 
        ($metrics['debt_amount'] / $metrics['total_income'] * 100) : 0;

    // Update top income and expenses query with same period clause
    $topIncomeQuery = "SELECT 
        src.source_name,
        COALESCE(SUM(t.amount), 0) as total
    FROM income_sources src
    LEFT JOIN transactions t ON (
        src.id = t.income_source_id 
        AND t.type = 'income'
        AND t.status = 'completed'
        AND $where_clause
    )
    GROUP BY src.id, src.source_name
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 5";

    $topExpensesQuery = "SELECT 
        ec.category_name,
        COALESCE(SUM(t.amount), 0) as total
    FROM expense_categories ec
    LEFT JOIN transactions t ON (
        ec.id = t.expense_category_id 
        AND t.type = 'expense'
        AND t.status = 'completed'
        AND $where_clause
    )
    WHERE ec.category_name != 'Savings'
    GROUP BY ec.id, ec.category_name
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 5";

    $stmt = $conn->query($topIncomeQuery);
    $topIncome = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query($topExpensesQuery);
    $topExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly comparison data for last 6 months
    $monthlyComparisonQuery = "SELECT 
        DATE_FORMAT(date, '%b %Y') as month,
        SUM(CASE WHEN type = 'income' AND status = 'completed' THEN amount ELSE 0 END) as income,
        SUM(CASE WHEN type = 'expense' AND status = 'completed' THEN amount ELSE 0 END) as expenses,
        SUM(CASE WHEN type = 'expense' AND status = 'completed' 
            AND expense_category_id IN (SELECT id FROM expense_categories WHERE category_name = 'Savings')
            THEN amount ELSE 0 END) as savings
    FROM transactions 
    WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    AND status != 'deleted'
    GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%b %Y')
    ORDER BY DATE_FORMAT(date, '%Y-%m') ASC";

    $stmt = $conn->query($monthlyComparisonQuery);
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Struktur data yang sama
    echo json_encode([
        'success' => true,
        'metrics' => [
            'net_cashflow' => floatval($net_cashflow),
            'savings_rate' => floatval($savings_rate),
            'expense_ratio' => floatval($expense_ratio),
            'debt_ratio' => floatval($debt_ratio)
        ],
        'monthly_comparison' => [
            'months' => array_column($monthlyData, 'month'),
            'income' => array_map('floatval', array_column($monthlyData, 'income')),
            'expenses' => array_map('floatval', array_column($monthlyData, 'expenses')),
            'net' => array_map(function($item) {
                return floatval($item['income']) - floatval($item['expenses']);
            }, $monthlyData)
        ]
    ]);

} catch(Exception $e) {
    error_log('Report Data Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}