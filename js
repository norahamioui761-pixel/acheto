document.addEventListener('DOMContentLoaded', function () {

    // ----- Mode clair / sombre -----
    var toggle = document.getElementById('themeToggle');
    var icon = document.getElementById('themeIcon');

    function appliquerTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    appliquerTheme(document.documentElement.getAttribute('data-theme') || 'light');

    if (toggle) {
        toggle.addEventListener('click', function () {
            var actuel = document.documentElement.getAttribute('data-theme');
            var nouveau = actuel === 'dark' ? 'light' : 'dark';
            appliquerTheme(nouveau);
            try { localStorage.setItem('acheto-theme', nouveau); } catch (e) {}
        });
    }

    // ----- Animation d'apparition au defilement -----
    var elements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (entree) {
                if (entree.isIntersecting) {
                    entree.target.classList.add('visible');
                    observer.unobserve(entree.target);
                }
            });
        }, { threshold: 0.12 });

        elements.forEach(function (el) { observer.observe(el); });
    } else {
        elements.forEach(function (el) { el.classList.add('visible'); });
    }
});
