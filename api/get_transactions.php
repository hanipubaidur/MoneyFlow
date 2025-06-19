<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT 
                t.id,
                t.date,
                t.type,
                t.amount,
                t.description,
                t.status,
                CASE 
                    WHEN t.type = 'income' THEN i.source_name
                    ELSE e.category_name
                END as category,
                t.savings_type
              FROM transactions t
              LEFT JOIN income_sources i ON t.income_source_id = i.id
              LEFT JOIN expense_categories e ON t.expense_category_id = e.id
              WHERE t.status != 'deleted'
              ORDER BY t.date DESC, t.id DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch(PDOException $e) {
    error_log('Get Transactions Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to load transactions']);
}
