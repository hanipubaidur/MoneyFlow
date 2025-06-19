<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get available savings transactions
    $query = "SELECT 
                t.id,
                t.amount,
                t.date,
                t.description,
                t.status,
                COALESCE(t.amount - IFNULL(
                    (
                    SELECT SUM(gt.amount)
                    FROM transactions gt
                    WHERE gt.source_transaction_id = t.id
                    AND gt.target_type = 'goal'
                    ), 0), t.amount) as available_amount
              FROM transactions t
              JOIN expense_categories ec ON t.expense_category_id = ec.id
              WHERE t.type = 'expense'
              AND ec.category_name = 'Savings'
              AND t.status != 'used_for_goal'
              HAVING available_amount > 0
              ORDER BY t.date DESC";

    $stmt = $conn->query($query);
    $savings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug log
    error_log('Savings Query: ' . $query);
    error_log('Available Savings: ' . json_encode($savings));

    echo json_encode($savings);

} catch(PDOException $e) {
    error_log('Savings fetch error: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}