<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();

    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('Transaction ID is required');
    }

    // Get transaction details first for rollback purposes
    $stmt = $conn->prepare("
        SELECT t.*, ec.category_name 
        FROM transactions t
        LEFT JOIN expense_categories ec ON t.expense_category_id = ec.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    // Jika ini transaksi savings dengan target, rollback progress
    if ($transaction['type'] === 'expense' && 
        $transaction['category_name'] === 'Savings' && 
        $transaction['savings_type'] === 'targeted') {
        
        $stmt = $conn->prepare("
            UPDATE savings_targets 
            SET current_amount = current_amount - ?,
                status = 'ongoing'
            WHERE id = ? AND status = 'achieved'
        ");
        $stmt->execute([$transaction['amount'], $transaction['savings_target_id']]);
    }

    // Hard delete transaction
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        // Update balance tracking
        $updateBalance = $conn->prepare("
            UPDATE balance_tracking 
            SET total_balance = total_balance + CASE 
                    WHEN ? = 'expense' THEN ?
                    ELSE -?
                END,
                total_savings = CASE 
                    WHEN ? = 'Savings' THEN total_savings - ?
                    ELSE total_savings
                END
            WHERE id = 1");
        
        $updateBalance->execute([
            $transaction['type'],
            $transaction['amount'],
            $transaction['amount'],
            $transaction['category_name'],
            $transaction['amount']
        ]);

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete transaction');
    }

} catch(Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
