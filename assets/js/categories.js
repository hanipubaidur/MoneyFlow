function saveCategory() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    
    fetch('api/add_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            $('#categoryModal').modal('hide');
            form.reset();
            
            Swal.fire({
                icon: 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 1500
            });
            
            // Reload categories list
            loadCategories();
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    });
}

// Show/hide budget limit field based on category type
document.getElementById('categoryType').addEventListener('change', function() {
    const budgetField = document.getElementById('budgetLimitField');
    budgetField.style.display = this.value === 'expense' ? 'block' : 'none';
});