// Utility perlindungan XSS untuk output di JS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global Toast Notifikasi gaya shadcn
function showToast(message, type = 'success') {
    const $container = $('#toastContainer');
    if ($container.length === 0) return;

    const isError = type === 'error';
    const iconSvg = isError 
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

    const $toast = $(`
        <div class="toast" style="${isError ? 'border-color: #ef4444;' : ''}">
            ${iconSvg}
            <span>${escapeHtml(message)}</span>
        </div>
    `);

    $container.append($toast);

    setTimeout(() => {
        $toast.fadeOut(200, function() {
            $(this).remove();
        });
    }, 3200);
}

// Global Logout Handler
$(document).on('click', '#logoutBtn', function() {
    $.ajax({
        url: '/api/logout',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        },
        complete: function() {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
    });
});
