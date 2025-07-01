<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    $id = $_GET['id'] ?? null;
    if (!$id) throw new Exception('No ID provided');

    // Cek apakah ini transfer_out atau transfer_in
    $query = "SELECT * FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $trx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trx) throw new Exception('Transaction not found');

    // Jika transfer, cari pasangan
    if (
        $trx['type'] === 'expense' && is_null($trx['income_source_id']) && is_null($trx['expense_category_id'])
        || $trx['type'] === 'income' && is_null($trx['income_source_id']) && is_null($trx['expense_category_id'])
    ) {
        // Cari pasangan
        $pair = $conn->prepare("SELECT * FROM transactions WHERE 
            id != ? AND 
            amount = ? AND 
            date = ? AND 
            description = ? AND 
            status = 'completed' AND
            (
                (type = 'income' AND ? = 'expense') OR
                (type = 'expense' AND ? = 'income')
            ) AND
            income_source_id IS NULL AND expense_category_id IS NULL
            LIMIT 1
        ");
        $pair->execute([
            $trx['id'],
            $trx['amount'],
            $trx['date'],
            $trx['description'],
            $trx['type'],
            $trx['type']
        ]);
        $pairTrx = $pair->fetch(PDO::FETCH_ASSOC);

        if (!$pairTrx) throw new Exception('Transfer pair not found');

        // Kembalikan data transfer
        echo json_encode([
            'type' => 'transfer',
            'amount' => $trx['amount'],
            'date' => $trx['date'],
            'description' => $trx['description'],
            'transfer_from' => $trx['type'] === 'expense' ? $trx['account_id'] : $pairTrx['account_id'],
            'transfer_to' => $trx['type'] === 'income' ? $trx['account_id'] : $pairTrx['account_id'],
            'transaction_id' => $trx['id'],
            'pair_id' => $pairTrx['id']
        ]);
        exit;
    }

    // Bukan transfer, kembalikan data biasa
    echo json_encode($trx);

} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
