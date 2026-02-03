<?php
/**
 * OpenElo - Main Router
 */

define('SRC_PATH', dirname(__DIR__) . '/src');

require_once SRC_PATH . '/config.php';
require_once SRC_PATH . '/lang.php';
require_once SRC_PATH . '/db.php';

$lang = getCurrentLang();
$page = $_GET['page'] ?? 'home';

// Valid pages
$validPages = ['home', 'circuits', 'circuit', 'create', 'submit', 'confirm', 'api'];

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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <a href="?" class="logo">Open<span>Elo</span></a>
            <nav class="nav">
                <a href="?page=circuits" <?= $page === 'circuits' || $page === 'circuit' ? 'class="active"' : '' ?>><?= __('nav_circuits') ?></a>
                <a href="?page=submit" <?= $page === 'submit' ? 'class="active"' : '' ?>><?= __('nav_submit_result') ?></a>
                <a href="?page=create" <?= $page === 'create' ? 'class="active"' : '' ?>><?= __('nav_create') ?></a>
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
        <p><?= __('footer_text') ?> <a href="https://github.com/openelo/openelo"><?= __('footer_github') ?></a></p>
    </footer>

    <script>
    function changeLang(lang) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
    </script>
</body>
</html>
