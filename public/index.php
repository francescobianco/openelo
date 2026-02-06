<?php
/**
 * OpenElo - Main Router
 */

define('SRC_PATH', dirname(__DIR__) . '/src');

// Helper function for asset paths (adds 'public/' prefix when accessed from root index.php)
function asset($path) {
    return (defined('ROOT_MODE') && ROOT_MODE ? 'public/' : '') . $path;
}

require_once SRC_PATH . '/config.php';
require_once SRC_PATH . '/lang.php';
require_once SRC_PATH . '/db.php';

// Initialize language before any output (sets cookie if needed)
initLang();
$lang = getCurrentLang();
$page = $_GET['page'] ?? 'home';

// Valid pages
$validPages = ['home', 'circuits', 'clubs', 'players', 'circuit', 'club', 'player', 'player_history', 'create', 'submit', 'confirm', 'match', 'deletion', 'contact', 'about', 'api'];

if (!in_array($page, $validPages)) {
    $page = 'home';
}

// API returns JSON, handle separately
if ($page === 'api') {
    require_once SRC_PATH . '/pages/api.php';
    exit;
}

// Start output buffering for page content
ob_start();
require_once SRC_PATH . '/pages/' . $page . '.php';
$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('site_title') ?> - <?= __('site_tagline') ?></title>
    <meta name="description" content="<?= __('site_description') ?>">
    <link rel="icon" type="image/x-icon" href="<?= asset('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
    <!-- Mobile menu overlay -->
    <div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>

    <?php if (APP_ENV !== ''): ?>
    <div class="env-banner"><?= strtoupper(APP_ENV) ?> — <?= $lang === 'it' ? 'Questo è un ambiente di ' . APP_ENV . ', non la produzione' : 'This is a ' . APP_ENV . ' environment, not production' ?></div>
    <?php endif; ?>

    <header class="header">
        <div class="header-inner">
            <a href="?" class="logo">
                <img src="<?= asset('logo.png') ?>" alt="OpenELO Logo" class="logo-img">
                <span class="logo-text">Open<span>ELO</span></span>
            </a>
            <div class="mobile-actions">
                <a href="?page=submit" class="nav-submit-btn <?= $page === 'submit' ? 'active' : '' ?>"><?= __('nav_submit_result') ?></a>
                <button class="hamburger" id="hamburger" onclick="toggleMobileMenu()" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <nav class="nav" id="mobile-nav">
                <div class="nav-links">
                    <a href="?page=about" <?= $page === 'about' ? 'class="active"' : '' ?>><?= $lang === 'it' ? 'Chi Siamo' : 'About' ?></a>
                    <a href="?page=circuits" <?= $page === 'circuits' || $page === 'circuit' ? 'class="active"' : '' ?>><?= __('nav_circuits') ?></a>
                    <a href="?page=clubs" <?= $page === 'clubs' || $page === 'club' ? 'class="active"' : '' ?>><?= __('nav_clubs') ?></a>
                    <a href="?page=players" <?= $page === 'players' || $page === 'player' ? 'class="active"' : '' ?>><?= __('nav_players') ?></a>
                    <a href="?page=create" <?= $page === 'create' ? 'class="active"' : '' ?>><?= __('nav_create') ?></a>
                    <a href="?page=submit" class="nav-submit-btn <?= $page === 'submit' ? 'active' : '' ?>"><?= __('nav_submit_result') ?></a>
                </div>
                <div class="nav-separator"></div>
                <select class="lang-select" onchange="changeLang(this.value)">
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="it" <?= $lang === 'it' ? 'selected' : '' ?>>Italiano</option>
                </select>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="footer">
        <p>
            <img src="<?= asset('logo.png') ?>" alt="OpenELO" style="height: 18px; width: 18px; vertical-align: middle; margin-right: 0; margin-top: -3px"> <strong style="color: #fff;">Open<span style="color: #4361ee;">ELO</span></strong> <?= __('footer_text') ?> <a href="https://github.com/openelo/openelo"><?= __('footer_github') ?></a>
            ~
            <a href="?page=about"><?= $lang === 'it' ? 'Chi Siamo' : 'About' ?></a>
            ~
            <a href="?page=contact"><?= $lang === 'it' ? 'Contatti' : 'Contact' ?></a>
        </p>
        <div class="footer-lang-select">
            <select class="lang-select" onchange="changeLang(this.value)">
                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                <option value="it" <?= $lang === 'it' ? 'selected' : '' ?>>Italiano</option>
            </select>
        </div>
    </footer>

    <script>
    function changeLang(lang) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }

    // Mobile menu toggle
    function toggleMobileMenu() {
        const nav = document.getElementById('mobile-nav');
        const hamburger = document.getElementById('hamburger');
        const overlay = document.getElementById('mobile-overlay');

        nav.classList.toggle('active');
        hamburger.classList.toggle('active');
        overlay.classList.toggle('active');

        if (nav.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Modal management
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Close modal on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal(e.target.id);
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) {
                closeModal(activeModal.id);
            }
        }
    });
    </script>
</body>
</html>
