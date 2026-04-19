// Простая проверка что JS работает
console.log('✅ Admin JS loaded');

// Ban/Unban - максимально просто
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM loaded');

    var banButtons = document.querySelectorAll('.btn-action.ban');
    console.log('🔍 Found ban buttons:', banButtons.length);

    banButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var username = this.getAttribute('data-username');
            var status = this.getAttribute('data-status');
            var newStatus = status === 'active' ? 'banned' : 'active';

            if (!confirm('Ban ' + username + '?')) return;

            fetch('/api/ban.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        status: newStatus
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.error) {
                        alert('Error: ' + data.error);
                    } else {
                        location.reload();
                    }
                })
                .catch(function(e) {
                    alert('Error: ' + e.message);
                });
        });
    });

    // Delete
    var deleteButtons = document.querySelectorAll('.btn-action.delete');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var username = this.getAttribute('data-username');
            var id = this.getAttribute('data-id');

            if (!confirm('Delete ' + username + '?')) return;

            fetch('/api/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        id: parseInt(id)
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.error) {
                        alert('Error: ' + data.error);
                    } else {
                        location.reload();
                    }
                })
                .catch(function(e) {
                    alert('Error: ' + e.message);
                });
        });
    });
});