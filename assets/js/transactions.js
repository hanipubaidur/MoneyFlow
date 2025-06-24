function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    initializeTypeSwitch();
    
    // Event listener for form sudah ada di sini, tidak perlu double
    document.getElementById('transactionForm').addEventListener('submit', handleFormSubmit);
});

// Handle type switching
function initializeTypeSwitch() {
    const typeSelect = document.getElementById('transactionType');
    const categorySelect = document.querySelector('select[name="category"]');
    const incomeGroup = document.getElementById('incomeSourceGroup');
    const expenseGroup = document.getElementById('expenseCategoryGroup');
    const savingsSection = document.getElementById('savingsTargetSection');
    const savingsMessage = document.getElementById('savingsMessage');
    
    function updateFormView() {
        const isExpense = typeSelect.value === 'expense';
        
        // Toggle optgroup display
        incomeGroup.style.display = isExpense ? 'none' : 'block';
        expenseGroup.style.display = isExpense ? 'block' : 'none';
        
        // Toggle individual options and update select value
        const incomeOptions = document.querySelectorAll('.income-option');
        const expenseOptions = document.querySelectorAll('.expense-option');
        
        incomeOptions.forEach(opt => opt.style.display = isExpense ? 'none' : '');
        expenseOptions.forEach(opt => opt.style.display = isExpense ? '' : 'none');

        // Set appropriate default option
        if (isExpense) {
            const firstExpenseOption = categorySelect.querySelector('.expense-option');
            if (firstExpenseOption) categorySelect.value = firstExpenseOption.value;
        } else {
            const firstIncomeOption = categorySelect.querySelector('.income-option');
            if (firstIncomeOption) categorySelect.value = firstIncomeOption.value;
        }

        // Update savings section visibility
        updateSavingsSection();
    }

    function updateSavingsSection() {
        const selectedOption = categorySelect.selectedOptions[0];
        const isSavings = selectedOption && selectedOption.text.trim() === 'Savings';
        
        savingsSection.style.display = (typeSelect.value === 'expense' && isSavings) ? 'block' : 'none';
        
        if (isSavings) {
            const targetId = document.getElementById('savingsTargetSelect').value;
            const targetName = targetId ? 
                document.getElementById('savingsTargetSelect').selectedOptions[0].text.split('(')[0].trim() : 
                'general savings';
            savingsMessage.textContent = `This amount will be allocated to ${targetName}`;
        }
    }

    // Event listeners
    typeSelect.addEventListener('change', updateFormView);
    categorySelect.addEventListener('change', updateSavingsSection);
    document.getElementById('savingsTargetSelect')?.addEventListener('change', updateSavingsSection);

    // Initial setup
    updateFormView();
}

// Hapus loadGoals function karena tidak diperlukan lagi

async function loadTransactions() {
    try {
        const response = await fetch('api/get_transactions.php');
        const transactions = await response.json();
        
        const tbody = document.querySelector('#transactionsTable tbody');
        if (!tbody) return;

        if (!Array.isArray(transactions) || transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No transactions found
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = transactions.map(t => `
            <tr>
                <td>${new Date(t.date).toLocaleDateString('id-ID')}</td>
                <td>
                    <i class='bx ${t.type === 'income' ? 'bx-plus text-success' : 'bx-minus text-danger'}'></i>
                    ${t.type}
                </td>
                <td>${t.category}</td>
                <td class="${t.type === 'income' ? 'text-success' : 'text-danger'}">
                    ${t.type === 'income' ? '+' : '-'} 
                    ${new Intl.NumberFormat('id-ID').format(t.amount)}
                </td>
                <td>
                    ${
                        t.account_name
                            ? `${t.account_name}${t.account_type ? ' (' + capitalize(t.account_type) + ')' : ''}`
                            : '-'
                    }
                </td>
                <td>${t.description || '-'}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-danger" onclick="deleteTransaction(${t.id})">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error loading transactions:', error);
        Swal.fire('Error', 'Failed to load transactions', 'error');
    }
}

// Tambahkan fungsi helper untuk kapitalisasi tipe akun
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Add event listener to load transactions when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadTransactions(); // Add this line
    initializeTypeSwitch();
    
    // Event listener for form sudah ada di sini, tidak perlu double
    document.getElementById('transactionForm').addEventListener('submit', handleFormSubmit);
});

function editTransaction(id) {
    fetch(`api/get_transaction.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            const form = document.getElementById('transactionForm');
            // Set form values
            form.querySelector('[name="type"]').value = data.type;
            form.querySelector('[name="amount"]').value = data.amount;
            form.querySelector('[name="account_id"]').value = data.account_id || '';
            form.querySelector('[name="date"]').value = data.date;
            form.querySelector('[name="description"]').value = data.description || '';

            // Trigger type change to show correct category options
            document.getElementById('transactionType').dispatchEvent(new Event('change'));

            // Set correct category option
            const categoryValue = `${data.type}_${data.type === 'income' ? data.income_source_id : data.expense_category_id}`;
            form.querySelector('[name="category"]').value = categoryValue;

            // Set account
            if (form.querySelector('[name="account_id"]')) {
                form.querySelector('[name="account_id"]').value = data.account_id || '';
            }

            // Add transaction ID for update
            if (!form.querySelector('[name="transaction_id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'transaction_id';
                form.appendChild(idInput);
            }
            form.querySelector('[name="transaction_id"]').value = id;

            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
            Swal.fire('Error', error.message, 'error');
        });
}

function deleteTransaction(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`api/delete_transaction.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Deleted!',
                        'Transaction has been deleted.',
                        'success'
                    ).then(() => {
                        loadTransactions();
                    });
                } else {
                    throw new Error(data.message || 'Failed to delete');
                }
            })
            .catch(error => {
                Swal.fire('Error!', error.message, 'error');
            });
        }
    });
}

// Pindahkan handler form ke function terpisah
async function handleFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        // Check if this is a savings transaction
        const categoryOption = form.querySelector('select[name="category"]').selectedOptions[0];
        if (categoryOption.text.trim() === 'Savings') {
            const targetSelect = form.querySelector('#savingsTargetSelect');
            if (targetSelect.value) {
                const targetOption = targetSelect.selectedOptions[0];
                const remaining = parseFloat(targetOption.dataset.remaining);
                const amount = parseFloat(formData.get('amount'));
                
                if (amount > remaining) {
                    throw new Error(`Amount exceeds remaining target (${formatCurrency(remaining)})`);
                }
                
                formData.append('savings_target_id', targetSelect.value);
            }
        }
        
        // Show loading
        Swal.fire({
            title: 'Saving...',
            didOpen: () => Swal.showLoading()
        });

        const response = await fetch('api/save_transaction.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                form.reset();
                loadTransactions();
                typeSelect.value = 'income';
                typeSelect.dispatchEvent(new Event('change'));
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.message
        });
    }
}

function updateExpenseChart(data) {
    // Ambil warna dari CHART_COLORS yang sudah didefinisikan di main.js
    const colors = data.map(item => 
        window.CHART_COLORS?.expenseCategories[item.category_name] || '#858796'
    );

    window.chartInstances.expenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.category_name),
            datasets: [{
                data: data.map(item => parseFloat(item.total)),
                backgroundColor: colors,
                borderColor: '#fff',
                borderWidth: 1
            }]
        },

    });
    
}