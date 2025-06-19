<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get POST data
    $title = trim($_POST['title'] ?? '');
    $target_amount = filter_var($_POST['target_amount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $target_date = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($title)) {
        throw new Exception('Title is required');
    }

    if ($target_amount <= 0) {
        throw new Exception('Target amount must be greater than 0');
    }

    // Insert target
    $stmt = $conn->prepare("
        INSERT INTO savings_targets 
        (title, description, target_amount, target_date) 
        VALUES (?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $title,
        $description,
        $target_amount,
        $target_date
    ]);

    if (!$result) {
        throw new Exception('Failed to save savings target');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Savings target saved successfully'
    ]);

} catch(Exception $e) {
    error_log('Save Savings Target Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
