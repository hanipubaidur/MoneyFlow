<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ambil semua transaksi, deteksi transfer (income/expense tanpa kategori/source, tapi ada account_id)
    $query = "SELECT 
                t.id,
                t.date,
                t.type,
                t.amount,
                t.description,
                t.status,
                CASE 
                    WHEN t.type = 'income' AND t.income_source_id IS NULL AND t.expense_category_id IS NULL THEN 'transfer_in'
                    WHEN t.type = 'expense' AND t.income_source_id IS NULL AND t.expense_category_id IS NULL THEN 'transfer_out'
                    WHEN t.type = 'income' THEN i.source_name
                    ELSE e.category_name
                END as category,
                t.savings_type,
                t.account_id,
                a.account_name,
                a.account_type
              FROM transactions t
              LEFT JOIN income_sources i ON t.income_source_id = i.id
              LEFT JOIN expense_categories e ON t.expense_category_id = e.id
              LEFT JOIN accounts a ON t.account_id = a.id
              WHERE t.status != 'deleted'
              ORDER BY t.date DESC, t.id DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan transfer_out dan transfer_in yang berpasangan (by amount, date, description)
    $result = [];
    $transfer_pairs = [];
    foreach ($rows as $row) {
        if ($row['category'] === 'transfer_out') {
            // Cari pasangan transfer_in
            foreach ($rows as $row2) {
                if (
                    $row2['category'] === 'transfer_in' &&
                    $row2['amount'] == $row['amount'] &&
                    $row2['date'] == $row['date'] &&
                    $row2['description'] == $row['description'] &&
                    $row2['id'] != $row['id'] &&
                    !isset($transfer_pairs[$row2['id']]) &&
                    !isset($transfer_pairs[$row['id']])
                ) {
                    $result[] = [
                        'id' => $row['id'],
                        'pair_id' => $row2['id'],
                        'date' => $row['date'],
                        'type' => 'transfer',
                        'amount' => $row['amount'],
                        'description' => $row['description'],
                        'status' => $row['status'],
                        'category' => 'Transfer: ' . ($row['account_name'] ?: '-') . ' → ' . ($row2['account_name'] ?: '-'),
                        'account_name' => null,
                        'account_type' => null
                    ];
                    $transfer_pairs[$row['id']] = true;
                    $transfer_pairs[$row2['id']] = true;
                    break;
                }
            }
        } elseif ($row['category'] === 'transfer_in') {
            // Sudah diproses di atas, skip
            continue;
        } else {
            // Selalu masukkan transaksi non-transfer, tanpa cek $transfer_pairs
            $result[] = $row;
        }
    }

    echo json_encode($result);

} catch(PDOException $e) {
    error_log('Get Transactions Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to load transactions']);
}
