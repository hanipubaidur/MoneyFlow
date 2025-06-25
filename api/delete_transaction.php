<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ambil ID dari GET atau DELETE body
    $id = $_GET['id'] ?? null;
    if (!$id) throw new Exception('Missing transaction ID');

    // Ambil data transaksi
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    $trx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trx) throw new Exception('Transaction not found');

    $conn->beginTransaction();

    // Deteksi transfer (income/expense tanpa kategori/source)
    $isTransfer = (
        ($trx['type'] === 'income' || $trx['type'] === 'expense') &&
        is_null($trx['income_source_id']) &&
        is_null($trx['expense_category_id'])
    );

    // Soft delete transaksi utama
    $stmt = $conn->prepare("UPDATE transactions SET status = 'deleted' WHERE id = ?");
    $stmt->execute([$id]);

    if ($isTransfer) {
        // Cari pasangan transfer
        $pairType = $trx['type'] === 'income' ? 'expense' : 'income';
        $stmt2 = $conn->prepare(
            "UPDATE transactions SET status = 'deleted'
             WHERE type = ? AND amount = ? AND date = ? AND description = ? 
             AND income_source_id IS NULL AND expense_category_id IS NULL
             AND status != 'deleted' AND id != ?");
        $stmt2->execute([
            $pairType, $trx['amount'], $trx['date'], $trx['description'], $trx['id']
        ]);
    }

    $conn->commit();

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('Delete Transaction Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
                    