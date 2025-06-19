<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT 
        COALESCE(SUM(t.amount), 0) as total_savings,
        COUNT(t.id) as transaction_count,
        MAX(t.date) as last_transaction
    FROM transactions t
    JOIN expense_categories ec ON t.expense_category_id = ec.id
    WHERE t.type = 'expense' 
    AND t.status = 'completed'
    AND ec.category_name = 'Savings'";

    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_savings' => floatval($result['total_savings']),
        'transaction_count' => intval($result['transaction_count']),
        'last_update' => $result['last_transaction']
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
