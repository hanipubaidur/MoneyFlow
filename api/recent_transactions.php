<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ambil 20 transaksi terakhir (lebih banyak untuk pairing transfer)
    $query = "SELECT 
                t.id,
                t.date,
                t.type,
                t.amount,
                t.account_id,
                t.description,
                CASE 
                    WHEN t.type = 'income' AND t.income_source_id IS NULL AND t.expense_category_id IS NULL THEN 'transfer_in'
                    WHEN t.type = 'expense' AND t.income_source_id IS NULL AND t.expense_category_id IS NULL THEN 'transfer_out'
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
              LIMIT 20";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan transfer_out dan transfer_in
    $result = [];
    $transfer_pairs = [];
    foreach ($rows as $row) {
        if ($row['category'] === 'transfer_out') {
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
                        'date' => date('d M Y', strtotime($row['date'])),
                        'type' => 'transfer',
                        'amount' => $row['amount'],
                        'account_id' => null,
                        'description' => $row['description'],
                        'category' => 'Transfer: ' . ($row['account_name'] ?: '-') . ' → ' . ($row2['account_name'] ?: '-'),
                        'account_name' => '-'
                    ];
                    $transfer_pairs[$row['id']] = true;
                    $transfer_pairs[$row2['id']] = true;
                    break;
                }
            }
        } elseif ($row['category'] !== 'transfer_in') {
            // Format date
            $row['date'] = date('d M Y', strtotime($row['date']));
            $result[] = $row;
        }
    }

    // Ambil 5 transaksi terbaru
    usort($result, function($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']) ?: $b['id'] <=> $a['id'];
    });
    $result = array_slice($result, 0, 5);

    echo json_encode($result);

} catch(PDOException $e) {
    error_log('Recent Transactions Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load transactions']);
}
