document.addEventListener('DOMContentLoaded', function() {

    // ============================================================
    // Sidebar toggle (mobile)
    // ============================================================
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // ============================================================
    // Settings sub-menu toggle
    // ============================================================
    const settingsGroup  = document.getElementById('settingsGroup');
    const settingsToggle = document.getElementById('settingsToggle');

    if (settingsGroup && settingsToggle) {
        settingsToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = settingsGroup.classList.toggle('is-open');
            settingsToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        settingsToggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                settingsToggle.click();
            }
        });
    }

    // ============================================================
    // Delete confirmation
    // ============================================================
    document.querySelectorAll('.confirm-delete').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm('Are you sure you want to delete this record?')) e.preventDefault();
        });
    });

    // ============================================================
    // Conditional field toggles
    // ============================================================
    document.querySelectorAll('[data-toggle-target]').forEach(el => {
        const update = () => {
            const target = document.querySelector(el.dataset.toggleTarget);
            if (target) target.style.display = (el.value === el.dataset.toggleValue) ? 'block' : 'none';
        };
        el.addEventListener('change', update);
        update();
    });

    // ============================================================
    // Show file name
    // ============================================================
    document.querySelectorAll('input[type=file]').forEach(inp => {
        inp.addEventListener('change', e => {
            const label = e.target.nextElementSibling;
            if (label && label.classList.contains('file-name')) {
                label.textContent = e.target.files[0]?.name || '';
            }
        });
    });

    // ============================================================
    // Auto-calculate age from DOB
    // ============================================================
    const dob = document.getElementById('dob');
    const age = document.getElementById('age');
    if (dob && age) {
        dob.addEventListener('change', () => {
            const d = new Date(dob.value);
            if (!isNaN(d)) {
                const diff = Date.now() - d.getTime();
                age.value = Math.floor(diff / (1000*60*60*24*365.25));
            }
        });
    }
});