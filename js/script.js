// Faye WorkNest - shared front-end helpers

function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Auto-submit checkbox / select toggles (todos, mark as read, status changes, etc.)
document.addEventListener('change', function (e) {
    if (e.target.matches('.auto-submit-checkbox')) {
        e.target.closest('form').submit();
    }
});

// Confirm before delete
document.addEventListener('click', function (e) {
    if (e.target.matches('.confirm-delete')) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    }
});

// Theme toggle (light/dark), persisted in localStorage
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('themeToggle');
    if (!toggle) return;
    var root = document.documentElement;
    function refreshIcon() {
        toggle.textContent = root.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
    }
    refreshIcon();
    toggle.addEventListener('click', function () {
        if (root.getAttribute('data-theme') === 'dark') {
            root.removeAttribute('data-theme');
            localStorage.setItem('fwn_theme', 'light');
        } else {
            root.setAttribute('data-theme', 'dark');
            localStorage.setItem('fwn_theme', 'dark');
        }
        refreshIcon();
    });
});

// Render conic-gradient circular progress rings.
// Usage: <div class="ring" data-progress="70" data-color="#6c5ce7"><span>70%</span></div>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ring[data-progress]').forEach(function (ring) {
        var pct = parseInt(ring.getAttribute('data-progress'), 10) || 0;
        var color = ring.getAttribute('data-color') || '#6c5ce7';
        var track = ring.getAttribute('data-track') || getComputedStyle(document.documentElement).getPropertyValue('--border').trim() || '#ece9f7';
        var deg = (pct / 100) * 360;
        ring.style.background = 'conic-gradient(' + color + ' ' + deg + 'deg, ' + track + ' ' + deg + 'deg)';
    });
});