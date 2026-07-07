/**
 * Realtime status polling for book listings
 */
document.addEventListener('DOMContentLoaded', function () {
    const statusContainers = document.querySelectorAll('[data-book-status-id]');
    if (statusContainers.length === 0) return;

    async function refreshStatuses() {
        const ids = [...statusContainers].map(el => el.dataset.bookStatusId);
        try {
            const res = await fetch('api/status.php?ids=' + ids.join(','));
            const data = await res.json();
            if (!data.success) return;

            data.books.forEach(book => {
                const el = document.querySelector('[data-book-status-id="' + book.book_id + '"]');
                if (!el) return;
                const badge = el.querySelector('.status-badge');
                if (badge) {
                    badge.textContent = book.book_status.charAt(0).toUpperCase() + book.book_status.slice(1);
                    badge.dataset.status = book.book_status;
                    badge.className = 'badge status-badge bg-' + statusColor(book.book_status);
                }
            });
        } catch (e) {
            console.warn('Status refresh failed', e);
        }
    }

    function statusColor(status) {
        const map = { available: 'success', reserved: 'warning', sold: 'secondary', rented: 'info' };
        return map[status] || 'secondary';
    }

    refreshStatuses();
    setInterval(refreshStatuses, 10000);
});
