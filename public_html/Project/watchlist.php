function confirmDelete(profileId) {
    if (confirm("Are you sure you want to remove this association?")) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_association.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'profile_id';
        input.value = profileId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}