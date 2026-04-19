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

session_start();

// Initialize language before any output (sets cookie if needed)
initLang();
$lang = getCurrentLang();
$page = $_GET['page'] ?? 'home';

// Valid pages
$validPages = ['home', 'circuits', 'clubs', 'players', 'matches', 'circuit', 'club', 'player', 'player_history', 'create', 'submit', 'confirm', 'match', 'deletion', 'contact', 'about', 'security', 'api'];

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
    <link rel="manifest" href="<?= asset('manifest.json') ?>">
    <meta name="theme-color" content="#4361ee">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="OpenELO">
    <link rel="apple-touch-icon" href="<?= asset('logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
</head>
<body>
    <!-- Mobile menu overlay -->
    <div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>

    <div class="sticky-top">
    <?php if (APP_ENV !== ''): ?>
    <div class="env-banner"><?= $lang === 'it'
        ? 'Ambiente di ' . APP_ENV . ' identificato dalla chiave "' . strtoupper(APP_ENV) . '" — I dati potranno essere persi o cancellati senza preavviso'
        : ucfirst(APP_ENV) . ' environment identified by key "' . strtoupper(APP_ENV) . '" — Data may be lost or deleted without notice'
    ?></div>
    <?php endif; ?>

    <header class="header">
        <div class="header-inner">
            <a href="./" class="logo">
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
                <a href="./" class="nav-mobile-logo">
                    <img src="<?= asset('logo.png') ?>" alt="OpenELO Logo" style="height: 36px; width: 36px; vertical-align: middle;">
                    <span style="font-size: 1.3rem; font-weight: 700; color: #fff;">Open<span style="color: #4361ee;">ELO</span></span>
                </a>
                <div class="nav-links">
                    <a href="?page=about" <?= $page === 'about' ? 'class="active"' : '' ?>><?= $lang === 'it' ? 'Chi Siamo' : 'About' ?></a>
                    <a href="?page=circuits" <?= $page === 'circuits' || $page === 'circuit' ? 'class="active"' : '' ?>><?= __('nav_circuits') ?></a>
                    <a href="?page=clubs" <?= $page === 'clubs' || $page === 'club' ? 'class="active"' : '' ?>><?= __('nav_clubs') ?></a>
                    <a href="?page=players" <?= $page === 'players' || $page === 'player' ? 'class="active"' : '' ?>><?= __('nav_players') ?></a>
                    <a href="?page=matches" <?= $page === 'matches' || $page === 'match' ? 'class="active"' : '' ?>><?= __('nav_matches') ?></a>
                    <a href="?page=create" <?= $page === 'create' ? 'class="active"' : '' ?>><?= __('nav_create') ?></a>
                    <a href="?page=submit" class="nav-submit-btn <?= $page === 'submit' ? 'active' : '' ?>"><?= __('nav_submit_result') ?></a>
                </div>
                <div class="nav-separator"></div>
                <select class="lang-select" onchange="changeLang(this.value)">
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="it" <?= $lang === 'it' ? 'selected' : '' ?>>Italiano</option>
                </select>
                <div class="nav-separator"></div>
                <button id="pwa-install-btn" class="pwa-install-btn" style="display:none;" onclick="pwaInstall()">
                    &#8962; <?= $lang === 'it' ? 'Aggiungi alla Home' : 'Add to Home Screen' ?>
                </button>
                <p id="pwa-ios-hint" class="pwa-ios-hint" style="display:none;">
                    <?= $lang === 'it'
                        ? '&#8679; Tocca <strong>Condividi</strong> poi <strong>"Aggiungi a Home"</strong>'
                        : '&#8679; Tap <strong>Share</strong> then <strong>"Add to Home Screen"</strong>' ?>
                </p>
            </nav>
        </div>
    </header>
    </div>

    <main>
        <?= $content ?>
    </main>

    <footer class="footer">
        <p>
            <img src="<?= asset('logo.png') ?>" alt="OpenELO" style="height: 18px; width: 18px; vertical-align: middle; margin-right: 0; margin-top: -4px"> <strong style="color: #fff;">Open<span style="color: #4361ee;">ELO</span></strong> <?= __('footer_text') ?><br class="footer-break"> <a href="https://github.com/francescobianco/openelo"><?= __('footer_github') ?></a>
            ~
            <a href="?page=about"><?= $lang === 'it' ? 'Chi Siamo' : 'About' ?></a>
            ~
            <a href="?page=contact"><?= $lang === 'it' ? 'Contatti' : 'Contact' ?></a>
            ~
            <a href="?page=security"><?= $lang === 'it' ? 'Sicurezza & Privacy' : 'Security & Privacy' ?></a>
        </p>
        <div class="footer-lang-select">
            <select class="lang-select" onchange="changeLang(this.value)">
                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                <option value="it" <?= $lang === 'it' ? 'selected' : '' ?>>Italiano</option>
            </select>
        </div>
    </footer>

    <script>
    // PWA install
    var _pwaPrompt = null;
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        _pwaPrompt = e;
        var btn = document.getElementById('pwa-install-btn');
        if (btn) btn.style.display = 'flex';
    });
    function pwaInstall() {
        if (!_pwaPrompt) return;
        _pwaPrompt.prompt();
        _pwaPrompt.userChoice.then(function() { _pwaPrompt = null; });
        var btn = document.getElementById('pwa-install-btn');
        if (btn) btn.style.display = 'none';
    }
    // iOS Safari hint (no beforeinstallprompt support)
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.navigator.standalone;
    if (isIos) {
        var hint = document.getElementById('pwa-ios-hint');
        if (hint) hint.style.display = 'block';
    }

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

    // Build mobile tab dropdowns from .tabs elements
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.tabs').forEach(function(tabs) {
            var active = tabs.querySelector('.tab.active');
            if (!active) return;

            var dropdown = document.createElement('div');
            dropdown.className = 'tabs-dropdown';

            var trigger = document.createElement('button');
            trigger.className = 'tabs-dropdown-trigger';
            trigger.innerHTML = active.textContent.trim() + '<span class="tabs-chevron">&#9660;</span>';

            var menu = document.createElement('div');
            menu.className = 'tabs-dropdown-menu';

            tabs.querySelectorAll('.tab').forEach(function(link) {
                var a = document.createElement('a');
                a.href = link.href;
                a.textContent = link.textContent.trim();
                if (link.classList.contains('active')) a.classList.add('active');
                menu.appendChild(a);
            });

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('open');
                trigger.classList.toggle('open');
            });

            document.addEventListener('click', function() {
                menu.classList.remove('open');
                trigger.classList.remove('open');
            });

            dropdown.appendChild(trigger);
            dropdown.appendChild(menu);
            tabs.parentNode.insertBefore(dropdown, tabs);
        });
    });

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
