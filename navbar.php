<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/gif" href="/favicon.gif">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
        <link rel="stylesheet" href="/main.css">
        <script>
            // Apply saved theme ASAP to avoid flash
            (function(){
                try {
                    var t = localStorage.getItem('theme') || 'light';
                    document.documentElement.setAttribute('data-theme', t);
                } catch(e) {}
            })();
        </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-6">
    <div class="container position-relative">
        <a class="navbar-brand" href="/">imgBD</a>
        <!-- Centered BD Flag linking to home -->
        <a href="/" class="nav-flag position-absolute start-50 translate-middle-x d-flex align-items-center" aria-label="Home">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6" width="48" height="29" style="display:block">
                <rect width="10" height="6" fill="#006747"/>
                <circle cx="4.5" cy="3" r="2" fill="#da291c"/>
                <script xmlns=""/>
            </svg>
        </a>
                <div class="ms-auto d-flex align-items-center">
                        <button id="themeToggle" class="btn btn-sm btn-outline-secondary btn-toggle-theme" type="button" aria-label="Toggle dark mode">
                                <span id="themeIcon" style="font-size:14px;">🌙</span>
                        </button>
                </div>
    </div>
</nav>

<script>
    (function() {
        var key = 'theme';
        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            var icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = t === 'dark' ? '☀️' : '🌙';
        }
        try {
            var saved = localStorage.getItem(key) || 'light';
            applyTheme(saved);
        } catch (e) {
            applyTheme('light');
        }
        var btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', function() {
                var current = document.documentElement.getAttribute('data-theme') || 'light';
                var next = current === 'light' ? 'dark' : 'light';
                applyTheme(next);
                try { localStorage.setItem(key, next); } catch (e) {}
            });
        }
    })();
</script>

</body>
</html>