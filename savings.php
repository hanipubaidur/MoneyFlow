<?php
require_once 'config/database.php';
$pageTitle = "Savings & Targets";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get detailed savings summary
    $savingsQuery = "SELECT 
        COALESCE(SUM(CASE WHEN t.savings_type = 'general' THEN t.amount ELSE 0 END), 0) as general_savings,
        COUNT(CASE WHEN t.savings_type = 'general' THEN 1 END) as general_count,
        COALESCE(SUM(CASE WHEN t.savings_type = 'targeted' THEN t.amount ELSE 0 END), 0) as targeted_savings,
        COUNT(CASE WHEN t.savings_type = 'targeted' THEN 1 END) as targeted_count
    FROM transactions t
    JOIN expense_categories ec ON t.expense_category_id = ec.id
    WHERE ec.category_name = 'Savings'
    AND t.status = 'completed'";

    $savings = $conn->query($savingsQuery)->fetch(PDO::FETCH_ASSOC);

    // Get savings targets
    $targetsQuery = "SELECT * FROM savings_targets 
                     WHERE status != 'cancelled' 
                     ORDER BY FIELD(status, 'ongoing', 'achieved') ASC, created_at DESC";
    
    $targets = $conn->query($targetsQuery)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

ob_start();
?>

<div class="row">
    <!-- General Savings Card -->
    <div class="col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">General Savings</h5>
                <h3><?= number_format($savings['general_savings']) ?></h3>
                <small><?= $savings['general_count'] ?> transactions</small>
            </div>
        </div>
    </div>

    <!-- Targeted Savings Card -->
    <div class="col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Targeted Savings</h5>
                <h3><?= number_format($savings['targeted_savings']) ?></h3>
                <small><?= $savings['targeted_count'] ?> transactions</small>
            </div>
        </div>
    </div>
    
    <!-- Target Form -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Savings Targets</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                    Add Target
                </button>
            </div>
            <div class="card-body">
                <div class="row" id="targetsList">
                    <?php foreach($targets as $target): 
                        $progress = round(($target['current_amount'] / $target['target_amount']) * 100);
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>
                                    <?= htmlspecialchars($target['title']) ?>
                                    <?php if($target['status'] === 'achieved'): ?>
                                        <span class="badge bg-success">Achieved</span>
                                    <?php endif; ?>
                                </h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $progress ?>%">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                                <div class="small">
                                    Target: <?= number_format($target['target_amount']) ?>
                                    <br>
                                    Saved: <?= number_format($target['current_amount']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Target Modal -->
<div class="modal fade" id="addTargetModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Savings Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="savingsForm">
                    <div class="mb-3">
                        <label class="form-label">Target Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="target_amount" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Date (Optional)</label>
                        <input type="date" class="form-control" name="target_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSavingsTarget()">Save Target</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageScript = '<script src="assets/js/savings.js"></script>';
include 'includes/layout.php';
?>
