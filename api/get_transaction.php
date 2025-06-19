<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    echo json_encode($transaction);
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
