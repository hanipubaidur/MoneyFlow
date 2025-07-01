<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get form data
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    if ($type === 'transfer') {
        // Handle transfer: insert two transactions (expense from, income to)
        $from = $_POST['transfer_from'];
        $to = $_POST['transfer_to'];
        if ($from == $to) throw new Exception('Transfer source and destination must be different');

        // Edit transfer
        if (!empty($_POST['transaction_id']) && !empty($_POST['pair_id'])) {
            $trxId = $_POST['transaction_id'];
            $pairId = $_POST['pair_id'];
            $conn->beginTransaction();
            try {
                // Update expense (from)
                $query1 = "UPDATE transactions SET amount=?, date=?, description=?, account_id=? WHERE id=? AND type='expense'";
                $stmt1 = $conn->prepare($query1);
                if (!$stmt1->execute([$amount, $date, $description, $from, $trxId])) {
                    throw new Exception('Failed to update transfer (from)');
                }
                // Update income (to)
                $query2 = "UPDATE transactions SET amount=?, date=?, description=?, account_id=? WHERE id=? AND type='income'";
                $stmt2 = $conn->prepare($query2);
                if (!$stmt2->execute([$amount, $date, $description, $to, $pairId])) {
                    throw new Exception('Failed to update transfer (to)');
                }
                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Transfer updated successfully'
                ]);
            } catch(Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            exit;
        }

        $conn->beginTransaction();
        try {
            // Insert expense (from)
            $query1 = "INSERT INTO transactions 
                (type, amount, date, description, expense_category_id, account_id, status) 
                VALUES ('expense', ?, ?, ?, NULL, ?, 'completed')";
            $stmt1 = $conn->prepare($query1);
            if (!$stmt1->execute([$amount, $date, $description, $from])) {
                throw new Exception('Failed to save transfer (from)');
            }

            // Insert income (to)
            $query2 = "INSERT INTO transactions 
                (type, amount, date, description, income_source_id, account_id, status) 
                VALUES ('income', ?, ?, ?, NULL, ?, 'completed')";
            $stmt2 = $conn->prepare($query2);
            if (!$stmt2->execute([$amount, $date, $description, $to])) {
                throw new Exception('Failed to save transfer (to)');
            }

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Transfer saved successfully'
            ]);
        } catch(Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        exit;
    }

    // Parse category value (format: type_id)
    $category = $_POST['category'];
    $account_id = $_POST['account_id'] ?? null;
    list($categoryType, $categoryId) = explode('_', $category);

    // Set the appropriate column
    $sourceColumn = ($type === 'income') ? 'income_source_id' : 'expense_category_id';

    // Start transaction
    $conn->beginTransaction();

    try {
        // Check category for savings
        $stmt = $conn->prepare("SELECT category_name FROM expense_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $categoryName = $stmt->fetchColumn();
        
        // Set savings type if this is a savings transaction
        $savingsType = 'general'; // default value
        if ($type === 'expense' && $categoryName === 'Savings') {
            $savingsType = !empty($_POST['savings_target_id']) ? 'targeted' : 'general';
        }

        // Insert/update transaction
        if (isset($_POST['transaction_id'])) {
            $query = "UPDATE transactions 
                     SET type = ?, amount = ?, date = ?, description = ?,
                         $sourceColumn = ?, savings_type = ?, account_id = ?
                     WHERE id = ?";
            $params = [$type, $amount, $date, $description, $categoryId, 
                      $savingsType, $account_id, $_POST['transaction_id']];
        } else {
            $query = "INSERT INTO transactions 
                     (type, amount, date, description, $sourceColumn, 
                      savings_type, status, account_id) 
                     VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)";
            $params = [$type, $amount, $date, $description, $categoryId, 
                      $savingsType, $account_id];
        }

        $stmt = $conn->prepare($query);
        if (!$stmt->execute($params)) {
            throw new Exception('Failed to save transaction');
        }

        $transactionId = isset($_POST['transaction_id']) ? 
            $_POST['transaction_id'] : $conn->lastInsertId();

        // Update savings target if selected
        if ($type === 'expense' && $categoryName === 'Savings' && 
            !empty($_POST['savings_target_id'])) {

            // Ambil data target
            $stmt = $conn->prepare("SELECT target_amount, current_amount FROM savings_targets WHERE id = ? AND status = 'ongoing' FOR UPDATE");
            $stmt->execute([$_POST['savings_target_id']]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($target) {
                $remaining = $target['target_amount'] - $target['current_amount'];
                $amountToAdd = min($amount, $remaining);

                // Update hanya sebesar kekurangan target
                $stmt = $conn->prepare("
                    UPDATE savings_targets 
                    SET current_amount = current_amount + ?,
                        status = CASE 
                            WHEN current_amount + ? >= target_amount THEN 'achieved'
                            ELSE status 
                        END
                    WHERE id = ? AND status = 'ongoing'
                ");
                if (!$stmt->execute([$amountToAdd, $amountToAdd, $_POST['savings_target_id']])) {
                    throw new Exception('Failed to update savings target');
                }

                // Jika ada kelebihan, bisa dikembalikan ke balance atau abaikan (tidak masuk target)
                // Optional: Catat kelebihan sebagai expense biasa atau tampilkan pesan
                if ($amount > $amountToAdd) {
                    // Misal: logika pengembalian ke balance, atau hanya info
                    // $excess = $amount - $amountToAdd;
                    // ... (implementasi sesuai kebutuhan aplikasi)
                }
            }
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => isset($_POST['transaction_id']) ? 
                'Transaction updated successfully' : 
                'Transaction saved successfully'
        ]);

    } catch(Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Save Transaction Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
