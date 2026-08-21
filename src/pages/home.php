<?php
/**
 * Chess School Platform - Landing page
 *
 * New mission: a platform to run chess courses and chess schools, with the
 * independent instructor as the primary user.
 *
 * The previous landing page (decentralized Elo rating system) is preserved
 * untouched in src/pages/home_rating.php and is still reachable at
 * ?page=home_rating.
 */

require_once SRC_PATH . '/auth.php';

// Logged-in instructors go straight to their dashboard: the landing page is
// for visitors only.
if (isLoggedIn()) {
    header('Location: ?page=dashboard');
    exit;
}

$db = Database::get();

// Platform-wide numbers (only shown once there is something to show)
$stats = [
    'instructors' => (int)$db->query("SELECT COUNT(*) FROM instructors WHERE deleted_at IS NULL AND status = 'active'")->fetchColumn(),
    'classes'     => (int)$db->query("SELECT COUNT(*) FROM classes WHERE deleted_at IS NULL")->fetchColumn(),
    'students'    => (int)$db->query("SELECT COUNT(*) FROM students WHERE deleted_at IS NULL")->fetchColumn(),
];
?>

<section class="hero">
    <span class="hero-piece hero-piece-1" aria-hidden="true">♞</span>
    <span class="hero-piece hero-piece-2" aria-hidden="true">♜</span>
    <span class="hero-piece hero-piece-3" aria-hidden="true">♟</span>
    <span class="hero-piece hero-piece-4" aria-hidden="true">♛</span>
    <h1><?= __('school_hero_title') ?></h1>
    <p><?= __('school_hero_subtitle') ?></p>
    <div class="hero-buttons">
        <a href="?page=register" class="btn btn-primary"><?= __('school_hero_cta') ?></a>
        <a href="?page=login" class="btn btn-secondary"><?= __('school_hero_secondary') ?></a>
    </div>
</section>

<?php if ($stats['instructors'] > 0): ?>
<section class="stats">
    <div class="stat-card">
        <div class="number"><?= $stats['instructors'] ?></div>
        <div class="label"><?= getCurrentLang() === 'it' ? 'Istruttori' : 'Instructors' ?></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['classes'] ?></div>
        <div class="label"><?= __('nav_classes') ?></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['students'] ?></div>
        <div class="label"><?= __('nav_students') ?></div>
    </div>
</section>
<?php endif; ?>

<section class="features">
    <h2><?= __('school_features_title') ?></h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">♜</div>
            <h3><?= __('school_feature_1_title') ?></h3>
            <p><?= __('school_feature_1_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♝</div>
            <h3><?= __('school_feature_2_title') ?></h3>
            <p><?= __('school_feature_2_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♛</div>
            <h3><?= __('school_feature_3_title') ?></h3>
            <p><?= __('school_feature_3_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♚</div>
            <h3><?= __('school_feature_4_title') ?></h3>
            <p><?= __('school_feature_4_desc') ?></p>
        </div>
    </div>
</section>

<section class="how-it-works">
    <h2><?= __('school_how_it_works') ?></h2>
    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <span><?= __('school_step_1') ?></span>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <span><?= __('school_step_2') ?></span>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <span><?= __('school_step_3') ?></span>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <span><?= __('school_step_4') ?></span>
        </div>
    </div>
</section>

<section class="fsi-section">
    <div class="fsi-card">
        <h2><?= __('fsi_title') ?></h2>
        <p class="fsi-subtitle"><?= __('fsi_subtitle') ?></p>

        <div class="fsi-pills">
            <span class="fsi-pill">♟ <?= __('fsi_item_1') ?></span>
            <span class="fsi-pill">🏆 <?= __('fsi_item_2') ?></span>
            <span class="fsi-pill">🎒 <?= __('fsi_item_3') ?></span>
            <span class="fsi-pill">♜ <?= __('fsi_item_4') ?></span>
        </div>

        <p class="fsi-note"><?= __('fsi_note') ?></p>
    </div>
</section>

<section class="cta-final">
    <h2><?= __('school_cta_title') ?></h2>
    <div class="hero-buttons">
        <a href="?page=register" class="btn btn-primary"><?= __('school_hero_cta') ?></a>
    </div>
</section>
