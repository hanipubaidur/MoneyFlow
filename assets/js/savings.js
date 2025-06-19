function saveSavingsTarget() {
    const form = document.getElementById('savingsForm');
    const formData = new FormData(form);

    // Basic validation
    const title = formData.get('title');
    const amount = formData.get('target_amount');

    if (!title || !amount) {
        Swal.fire('Error', 'Title and amount are required', 'error');
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('api/save_savings_target.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const modal = document.getElementById('addTargetModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
            form.reset();
            
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Failed to save target', 'error');
    });
}

function deleteSavingsTarget(id) {
    Swal.fire({
        title: 'Delete Target?',
        text: "This action cannot be undone",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_savings_target.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    target_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
