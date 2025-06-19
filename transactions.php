<?php
require_once 'config/database.php';
$pageTitle = "Transactions";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get income sources and expense categories (tambahkan WHERE is_active = TRUE)
    $income_query = "SELECT * FROM income_sources WHERE is_active = TRUE ORDER BY source_name";
    $expense_query = "SELECT * FROM expense_categories WHERE is_active = TRUE ORDER BY category_name";

    $income_sources = $conn->query($income_query)->fetchAll(PDO::FETCH_ASSOC);
    $expense_categories = $conn->query($expense_query)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

ob_start();
?>

<!-- Transaction Form -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Add Transaction</h5>
    </div>
    <div class="card-body">
        <form id="transactionForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type" id="transactionType" required>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" id="categoryLabel">Source/Category</label>
                                <select class="form-select" name="category" required>
                                    <!-- Income Sources -->
                                    <optgroup label="Income Sources" id="incomeSourceGroup">
                                    <?php foreach($income_sources as $source): ?>
                                        <option value="income_<?php echo $source['id']; ?>" class="income-option">
                                            <?php echo htmlspecialchars($source['source_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                    
                                    <!-- Expense Categories -->
                                    <optgroup label="Expense Categories" id="expenseCategoryGroup" style="display:none;">
                                    <?php foreach($expense_categories as $category): ?>
                                        <option value="expense_<?php echo $category['id']; ?>" class="expense-option" style="display:none;">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="amount" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <!-- Savings Target Section -->
                    <div class="mb-3" id="savingsTargetSection" style="display:none;">
                        <label class="form-label">Savings Target</label>
                        <select class="form-select" name="savings_target_id" id="savingsTargetSelect">
                            <option value="">General Savings</option>
                            <?php 
                            // Get active savings targets
                            $targetsQuery = "SELECT id, title, target_amount, current_amount 
                                           FROM savings_targets 
                                           WHERE status = 'ongoing'
                                           ORDER BY created_at DESC";
                            $targets = $conn->query($targetsQuery)->fetchAll();
                            foreach($targets as $target): 
                                $remaining = $target['target_amount'] - $target['current_amount'];
                            ?>
                                <option value="<?= $target['id'] ?>" data-remaining="<?= $remaining ?>">
                                    <?= htmlspecialchars($target['title']) ?> 
                                    (Remaining: <?= number_format($remaining) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="alert alert-info mt-2">
                            <i class='bx bx-info-circle'></i>
                            <span id="savingsMessage">Select where to allocate this savings</span>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transaction List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
$pageScript = '<script src="assets/js/transactions.js"></script>';

include 'includes/layout.php';
?>
</script>