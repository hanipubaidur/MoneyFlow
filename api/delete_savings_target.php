<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['target_id'])) {
        throw new Exception('Target ID is required');
    }

    $targetId = $data['target_id'];
    
    // Update status to cancelled instead of deleting
    $stmt = $conn->prepare("UPDATE savings_targets SET status = 'cancelled' WHERE id = ?");
    $result = $stmt->execute([$targetId]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Savings target cancelled successfully'
        ]);
    } else {
        throw new Exception('Failed to cancel savings target');
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
