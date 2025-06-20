<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle delete (soft/hard)
if (isset($_POST['delete'])) {
    try {
        $id = $_POST['id'];
        $type = $_POST['type'];
        
        if ($type === 'income') {
            $checkQuery = "SELECT COUNT(*) FROM transactions WHERE income_source_id = ? AND status = 'completed'";
            $table = "income_sources";
        } else {
            $checkQuery = "SELECT COUNT(*) FROM transactions WHERE expense_category_id = ? AND status = 'completed'";
            $table = "expense_categories";
        }

        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$id]);
        $usageCount = $stmt->fetchColumn();

        if ($usageCount > 0) {
            // Soft delete
            $query = "UPDATE $table SET is_active = FALSE WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Successfully deactivated'
            ]);
        } else {
            // Hard delete
            $query = "DELETE FROM $table WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Successfully deleted'
            ]);
        }
        exit;
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle add
if (isset($_POST['add'])) {
    try {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            throw new Exception('Name is required');
        }

        if ($type === 'income') {
            $checkQuery = "SELECT COUNT(*) FROM income_sources WHERE source_name = ? AND is_active = TRUE";
            $insertQuery = "INSERT INTO income_sources (source_name, description) VALUES (?, ?)";
        } else {
            $checkQuery = "SELECT COUNT(*) FROM expense_categories WHERE category_name = ? AND is_active = TRUE";
            $insertQuery = "INSERT INTO expense_categories (category_name, description) VALUES (?, ?)";
        }

        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Name already exists');
        }

        $stmt = $conn->prepare($insertQuery);
        $success = $stmt->execute([$name, $description]);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Added successfully'
            ]);
        } else {
            throw new Exception('Failed to add');
        }
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get active income sources
$incomeSources = $conn->query("SELECT * FROM income_sources WHERE is_active = TRUE ORDER BY source_name")->fetchAll();

// Get active expense categories
$expenseCategories = $conn->query("SELECT * FROM expense_categories WHERE is_active = TRUE ORDER BY category_name")->fetchAll();

ob_start();
?>

<div class="row">
    <!-- Income Sources -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Income Sources</h5>
                <button type="button" class="btn btn-sm btn-success" onclick="addItem('income')">
                    <i class='bx bx-plus'></i> Add Source
                </button>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($incomeSources as $source): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($source['source_name']) ?></h6>
                            <?php if($source['description']): ?>
                                <small class="text-muted"><?= htmlspecialchars($source['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDelete(<?= $source['id'] ?>, 'income', '<?= htmlspecialchars($source['source_name']) ?>')">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Categories -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expense Categories</h5>
                <button type="button" class="btn btn-sm btn-success" onclick="addItem('expense')">
                    <i class='bx bx-plus'></i> Add Category
                </button>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($expenseCategories as $category): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($category['category_name']) ?></h6>
                            <?php if($category['description']): ?>
                                <small class="text-muted"><?= htmlspecialchars($category['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDelete(<?= $category['id'] ?>, 'expense', '<?= htmlspecialchars($category['category_name']) ?>')">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.delete-form {
    margin-left: 10px;
}
.list-group-item:hover {
    background-color: #f8f9fa;
}
.delete-form button {
    opacity: 0;
    transition: opacity 0.2s;
}
.list-group-item:hover .delete-form button {
    opacity: 1;
}
</style>

<script>
function confirmDelete(id, type, name) {
    Swal.fire({
        title: 'Are you sure?',
        html: `Do you want to delete <strong>${name}</strong>?<br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('delete', '1');
            formData.append('id', id);
            formData.append('type', type);

            fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: `${name} has been deleted.`,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.error || 'Failed to delete');
                }
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    error.message,
                    'error'
                );
            });
        }
    });
}

function addItem(type) {
    Swal.fire({
        title: `Add New ${type === 'income' ? 'Income Source' : 'Expense Category'}`,
        html: `
            <input type="text" id="swal-name" 
                   class="swal2-input" placeholder="Name" required>
            <textarea id="swal-description" 
                     class="swal2-textarea" placeholder="Description (optional)"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const name = document.getElementById('swal-name').value;
            const description = document.getElementById('swal-description').value;
            
            if (!name.trim()) {
                Swal.showValidationMessage('Name is required');
                return false;
            }

            const formData = new FormData();
            formData.append('add', '1');
            formData.append('type', type);
            formData.append('name', name);
            formData.append('description', description);

            return fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to add');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Added Successfully',
                text: result.value.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    }).catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}
</script>

<?php
$pageContent = ob_get_clean();
include 'includes/layout.php';
?>