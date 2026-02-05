<?php
/**
 * OpenElo - Home Page
 */

$db = Database::get();

// Get stats
$stats = [
    'circuits' => $db->query("SELECT COUNT(*) FROM circuits WHERE confirmed = 1")->fetchColumn(),
    'clubs' => $db->query("
        SELECT COUNT(*) FROM clubs c
        WHERE c.president_confirmed = 1
        AND EXISTS (SELECT 1 FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1)
    ")->fetchColumn(),
    'players' => $db->query("SELECT COUNT(*) FROM players WHERE confirmed = 1")->fetchColumn(),
    'matches' => $db->query("SELECT COUNT(*) FROM matches WHERE rating_applied = 1")->fetchColumn(),
];

// Get recent circuits
$circuits = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.circuit_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as club_count,
        (SELECT COUNT(DISTINCT r.player_id) FROM ratings r WHERE r.circuit_id = c.id) as player_count
    FROM circuits c
    WHERE c.confirmed = 1
    ORDER BY c.created_at DESC
    LIMIT 6
")->fetchAll();
?>

<section class="hero">
    <h1><?= __('hero_title') ?></h1>
    <p><?= __('hero_subtitle') ?></p>
    <div class="hero-buttons">
        <a href="?page=circuits" class="btn btn-primary"><?= __('hero_cta') ?></a>
        <a href="?page=create&highlight=circuit" class="btn btn-secondary"><?= __('hero_create') ?></a>
    </div>
</section>

<section class="stats">
    <div class="stat-card">
        <div class="number"><?= $stats['circuits'] ?></div>
        <div class="label"><?= __('nav_circuits') ?></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['clubs'] ?></div>
        <div class="label"><?= __('circuit_clubs') ?></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['players'] ?></div>
        <div class="label"><?= __('circuit_players') ?></div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['matches'] ?></div>
        <div class="label"><?= __('circuit_matches') ?></div>
    </div>
</section>

<section class="features">
    <h2><?= __('site_tagline') ?></h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">♜</div>
            <h3><?= __('feature_1_title') ?></h3>
            <p><?= __('feature_1_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♝</div>
            <h3><?= __('feature_2_title') ?></h3>
            <p><?= __('feature_2_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♛</div>
            <h3><?= __('feature_3_title') ?></h3>
            <p><?= __('feature_3_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">♚</div>
            <h3><?= __('feature_4_title') ?></h3>
            <p><?= __('feature_4_desc') ?></p>
        </div>
    </div>
</section>

<section class="how-it-works">
    <h2><?= __('how_it_works') ?></h2>
    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <span><?= __('step_1') ?></span>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <span><?= __('step_2') ?></span>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <span><?= __('step_3') ?></span>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <span><?= __('step_4') ?></span>
        </div>
        <div class="step">
            <div class="step-number">5</div>
            <span><?= __('step_5') ?></span>
        </div>
    </div>
</section>

<?php if (!empty($circuits)): ?>
<section class="circuits-section">
    <div class="section-header">
        <h2><?= __('nav_circuits') ?></h2>
        <a href="?page=circuits" class="btn btn-secondary"><?= __('hero_cta') ?></a>
    </div>
    <div class="circuits-grid">
        <?php foreach ($circuits as $circuit): ?>
        <div class="circuit-card">
            <h3><a href="?page=circuit&id=<?= $circuit['id'] ?>"><?= htmlspecialchars($circuit['name']) ?></a></h3>
            <div class="circuit-meta">
                <span>♜ <?= $circuit['club_count'] ?> <?= __('circuit_clubs') ?></span>
                <span>♟ <?= $circuit['player_count'] ?> <?= __('circuit_players') ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
