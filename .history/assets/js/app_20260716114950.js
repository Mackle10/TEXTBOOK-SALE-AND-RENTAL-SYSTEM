/**
 * Realtime status polling for book listings
 */
document.addEventListener('DOMContentLoaded', function () {
    // Book status polling (only run when status elements exist)
    const statusContainers = document.querySelectorAll('[data-book-status-id]');
    if (statusContainers.length > 0) {
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
    }

    // Password show/hide toggle helper
    document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = btn.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                btn.innerHTML = '<i class="bi bi-eye"></i>';
                btn.setAttribute('aria-label', 'Show password');
            }
        });
    });

    if (window.sellerPerformanceLabels && window.sellerPerformanceRevenue) {
        const chartCanvas = document.getElementById('sellerPerformanceChart');
        if (chartCanvas) {
            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: window.sellerPerformanceLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: window.sellerPerformanceRevenue,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.15)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#0d6efd',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#495057' } },
                        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#495057' }, beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false }
                    }
                }
            });
        }
    }
});
