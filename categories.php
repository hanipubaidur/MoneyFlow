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

// Handle add/delete for accounts
if (isset($_POST['add_account'])) {
    try {
        $name = trim($_POST['account_name']);
        $type = $_POST['account_type'];
        $description = trim($_POST['account_description'] ?? '');
        if (empty($name)) throw new Exception('Account name is required');
        $checkQuery = "SELECT COUNT(*) FROM accounts WHERE account_name = ? AND is_active = TRUE";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) throw new Exception('Account already exists');
        $stmt = $conn->prepare("INSERT INTO accounts (account_name, account_type, description) VALUES (?, ?, ?)");
        $success = $stmt->execute([$name, $type, $description]);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => 'Account added']);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['delete_account'])) {
    try {
        $id = $_POST['id'];
        // Soft delete if used, hard delete if not
        $check = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE account_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $stmt = $conn->prepare("UPDATE accounts SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Account deactivated';
        } else {
            $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Account deleted';
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle edit (category or account)
if (isset($_POST['edit'])) {
    try {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        if (empty($name)) throw new Exception('Name is required');
        if ($type === 'income') {
            $query = "UPDATE income_sources SET source_name = ?, description = ? WHERE id = ?";
        } else {
            $query = "UPDATE expense_categories SET category_name = ?, description = ? WHERE id = ?";
        }
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$name, $description, $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => 'Updated successfully']);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['edit_account'])) {
    try {
        $id = $_POST['id'];
        $name = trim($_POST['account_name']);
        $type = $_POST['account_type'];
        $description = trim($_POST['account_description'] ?? '');
        if (empty($name)) throw new Exception('Account name is required');
        $query = "UPDATE accounts SET account_name = ?, account_type = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$name, $type, $description, $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => 'Account updated']);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get active income sources
$incomeSources = $conn->query("SELECT * FROM income_sources WHERE is_active = TRUE ORDER BY source_name")->fetchAll();

// Get active expense categories
$expenseCategories = $conn->query("SELECT * FROM expense_categories WHERE is_active = TRUE ORDER BY category_name")->fetchAll();

// Get active accounts
$accounts = $conn->query("SELECT * FROM accounts WHERE is_active = TRUE ORDER BY account_type, account_name")->fetchAll();

ob_start();
?>

<div class="row g-4">
    <!-- Add Expense Category -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add Expense Category</h5>
            </div>
            <div class="card-body">
                <form id="addExpenseCategoryForm" onsubmit="event.preventDefault(); addItem('expense');">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="swal-name-expense" placeholder="e.g. Food, Shopping" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-control" id="swal-description-expense" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class='bx bx-plus'></i> Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Income Source -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add Income Source</h5>
            </div>
            <div class="card-body">
                <form id="addIncomeSourceForm" onsubmit="event.preventDefault(); addItem('income');">
                    <div class="mb-3">
                        <label class="form-label">Source Name</label>
                        <input type="text" class="form-control" id="swal-name-income" placeholder="e.g. Salary, Freelance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-control" id="swal-description-income" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class='bx bx-plus'></i> Add Source
                    </button>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Account -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add Account</h5>
            </div>
            <div class="card-body">
                <form id="addAccountForm" onsubmit="event.preventDefault(); addAccountFormSubmit();">
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="swal-account-name-form" placeholder="e.g. BCA, Dana, Cash" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select class="form-select" id="swal-account-type-form">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="e-wallet">E-Wallet</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-control" id="swal-account-description-form" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class='bx bx-plus'></i> Add Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Expense Categories List -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Expense Categories</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($expenseCategories as $category): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold"><?= htmlspecialchars($category['category_name']) ?></span>
                            <?php if($category['description']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($category['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                onclick="editCategory(<?= $category['id'] ?>, 'expense', '<?= htmlspecialchars(addslashes($category['category_name'])) ?>', '<?= htmlspecialchars(addslashes($category['description'])) ?>')"
                                title="Edit">
                                <i class='bx bx-edit'></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $category['id'] ?>, 'expense', '<?= htmlspecialchars($category['category_name']) ?>')"
                                title="Delete">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <!-- Income Sources List -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Income Sources</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($incomeSources as $source): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold"><?= htmlspecialchars($source['source_name']) ?></span>
                            <?php if($source['description']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($source['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                onclick="editCategory(<?= $source['id'] ?>, 'income', '<?= htmlspecialchars(addslashes($source['source_name'])) ?>', '<?= htmlspecialchars(addslashes($source['description'])) ?>')"
                                title="Edit">
                                <i class='bx bx-edit'></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $source['id'] ?>, 'income', '<?= htmlspecialchars($source['source_name']) ?>')"
                                title="Delete">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <!-- Accounts List -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Accounts (Bank / E-Wallet / Cash)</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($accounts as $acc): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold"><?= htmlspecialchars($acc['account_name']) ?></span>
                            <span class="badge bg-light text-dark ms-2"><?= ucfirst($acc['account_type']) ?></span>
                            <?php if($acc['description']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($acc['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_type'])) ?>', '<?= htmlspecialchars(addslashes($acc['description'])) ?>')"
                                title="Edit">
                                <i class='bx bx-edit'></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDeleteAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars($acc['account_name']) ?>')"
                                title="Delete">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editCategoryForm" onsubmit="event.preventDefault(); saveEditCategory();">
        <div class="modal-header">
          <h5 class="modal-title" id="editCategoryModalLabel">Edit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editCategoryId">
          <input type="hidden" id="editCategoryType">
          <div class="mb-3">
            <label class="form-label" id="editCategoryLabel"></label>
            <input type="text" class="form-control" id="editCategoryName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description (optional)</label>
            <textarea class="form-control" id="editCategoryDescription" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editAccountForm" onsubmit="event.preventDefault(); saveEditAccount();">
        <div class="modal-header">
          <h5 class="modal-title">Edit Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editAccountId">
          <div class="mb-3">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-control" id="editAccountName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Account Type</label>
            <select class="form-select" id="editAccountType">
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="e-wallet">E-Wallet</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description (optional)</label>
            <textarea class="form-control" id="editAccountDescription" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.list-group-item {
    transition: background 0.2s;
}
.list-group-item:hover {
    background-color: #f8f9fa;
}
.btn-outline-danger, .btn-outline-primary {
    opacity: 0.7;
    transition: opacity 0.2s;
}
.btn-outline-danger:hover, .btn-outline-primary:hover {
    opacity: 1;
}
.card-header {
    background: #f8f9fa;
}
</style>

<script>
// Overwrite addItem to use form values if available
function addItem(type) {
    let name, description;
    if (type === 'income') {
        name = document.getElementById('swal-name-income')?.value || '';
        description = document.getElementById('swal-description-income')?.value || '';
    } else {
        name = document.getElementById('swal-name-expense')?.value || '';
        description = document.getElementById('swal-description-expense')?.value || '';
    }
    if (!name.trim()) {
        Swal.fire('Error', 'Name is required', 'error');
        return;
    }
    const formData = new FormData();
    formData.append('add', '1');
    formData.append('type', type);
    formData.append('name', name);
    formData.append('description', description);

    fetch('categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) throw new Error(data.error || 'Failed to add');
        Swal.fire({
            icon: 'success',
            title: 'Added Successfully',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

function addAccountFormSubmit() {
    const name = document.getElementById('swal-account-name-form').value;
    const type = document.getElementById('swal-account-type-form').value;
    const description = document.getElementById('swal-account-description-form').value;
    if (!name.trim()) {
        Swal.fire('Error', 'Account name is required', 'error');
        return;
    }
    const formData = new FormData();
    formData.append('add_account', '1');
    formData.append('account_name', name);
    formData.append('account_type', type);
    formData.append('account_description', description);
    fetch('categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) throw new Error(data.error || 'Failed to add');
        Swal.fire({
            icon: 'success',
            title: 'Added Successfully',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

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

function addAccount() {
    Swal.fire({
        title: 'Add New Account',
        html: `
            <input type="text" id="swal-account-name" class="swal2-input" placeholder="Account Name" required>
            <select id="swal-account-type" class="swal2-input">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="e-wallet">E-Wallet</option>
                <option value="other">Other</option>
            </select>
            <textarea id="swal-account-description" class="swal2-textarea" placeholder="Description (optional)"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const name = document.getElementById('swal-account-name').value;
            const type = document.getElementById('swal-account-type').value;
            const description = document.getElementById('swal-account-description').value;
            if (!name.trim()) {
                Swal.showValidationMessage('Account name is required');
                return false;
            }
            const formData = new FormData();
            formData.append('add_account', '1');
            formData.append('account_name', name);
            formData.append('account_type', type);
            formData.append('account_description', description);
            return fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Failed to add');
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

function confirmDeleteAccount(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        html: `Delete <strong>${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('delete_account', '1');
            formData.append('id', id);
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
                        text: data.message,
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
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

function editCategory(id, type, name, description) {
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryType').value = type;
    document.getElementById('editCategoryName').value = name.replace(/\\'/g, "'");
    document.getElementById('editCategoryDescription').value = description.replace(/\\'/g, "'");
    document.getElementById('editCategoryLabel').textContent = type === 'income' ? 'Source Name' : 'Category Name';
    document.getElementById('editCategoryModalLabel').textContent = type === 'income' ? 'Edit Income Source' : 'Edit Expense Category';
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function saveEditCategory() {
    const id = document.getElementById('editCategoryId').value;
    const type = document.getElementById('editCategoryType').value;
    const name = document.getElementById('editCategoryName').value;
    const description = document.getElementById('editCategoryDescription').value;
    if (!name.trim()) {
        Swal.fire('Error', 'Name is required', 'error');
        return;
    }
    const formData = new FormData();
    formData.append('edit', '1');
    formData.append('id', id);
    formData.append('type', type);
    formData.append('name', name);
    formData.append('description', description);

    fetch('categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) throw new Error(data.error || 'Failed to update');
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

function editAccount(id, name, type, description) {
    document.getElementById('editAccountId').value = id;
    document.getElementById('editAccountName').value = name.replace(/\\'/g, "'");
    document.getElementById('editAccountType').value = type;
    document.getElementById('editAccountDescription').value = description.replace(/\\'/g, "'");
    new bootstrap.Modal(document.getElementById('editAccountModal')).show();
}

function saveEditAccount() {
    const id = document.getElementById('editAccountId').value;
    const name = document.getElementById('editAccountName').value;
    const type = document.getElementById('editAccountType').value;
    const description = document.getElementById('editAccountDescription').value;
    if (!name.trim()) {
        Swal.fire('Error', 'Account name is required', 'error');
        return;
    }
    const formData = new FormData();
    formData.append('edit_account', '1');
    formData.append('id', id);
    formData.append('account_name', name);
    formData.append('account_type', type);
    formData.append('account_description', description);

    fetch('categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) throw new Error(data.error || 'Failed to update');
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}
</script>

<?php
$pageContent = ob_get_clean();
include 'includes/layout.php';
?>