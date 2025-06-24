<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT 
                t.id,
                DATE_FORMAT(t.date, '%d %b %Y') as date,
                t.type,
                t.amount,
                t.account_id,
                t.description,
                CASE 
                    WHEN t.type = 'income' THEN i.source_name
                    ELSE e.category_name
                END as category,
                a.account_name
              FROM transactions t
              LEFT JOIN income_sources i ON t.income_source_id = i.id
              LEFT JOIN expense_categories e ON t.expense_category_id = e.id
              LEFT JOIN accounts a ON t.account_id = a.id
              WHERE t.status = 'completed'
              ORDER BY t.date DESC, t.id DESC
              LIMIT 5";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($transactions);

} catch(PDOException $e) {
    error_log('Recent Transactions Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load transactions']);
}
