<?php
/**
 * OpenElo - Home Page
 */

$db = Database::get();

// Get stats
$stats = [
    'circuits' => $db->query("SELECT COUNT(*) FROM circuits WHERE confirmed = 1 AND deleted_at IS NULL")->fetchColumn(),
    'clubs' => $db->query("
        SELECT COUNT(*) FROM clubs c
        WHERE c.president_confirmed = 1
        AND c.deleted_at IS NULL
        AND EXISTS (SELECT 1 FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1)
    ")->fetchColumn(),
    'players' => $db->query("
        SELECT COUNT(*) FROM players p
        JOIN clubs c ON c.id = p.club_id
        WHERE p.confirmed = 1 AND p.deleted_at IS NULL
          AND c.deleted_at IS NULL AND c.president_confirmed = 1
          AND EXISTS (SELECT 1 FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1)
    ")->fetchColumn(),
    'matches' => $db->query("
        SELECT COUNT(*) FROM matches m
        JOIN circuits ci ON ci.id = m.circuit_id
        JOIN players pw ON pw.id = m.white_player_id
        JOIN players pb ON pb.id = m.black_player_id
        JOIN clubs cw ON cw.id = pw.club_id
        JOIN clubs cb ON cb.id = pb.club_id
        WHERE m.deleted_at IS NULL AND ci.deleted_at IS NULL
          AND pw.deleted_at IS NULL AND pb.deleted_at IS NULL
          AND cw.deleted_at IS NULL AND cb.deleted_at IS NULL
    ")->fetchColumn(),
];

// Get recent circuits
$circuits = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.circuit_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as club_count,
        (SELECT COUNT(DISTINCT p.id) FROM players p JOIN circuit_clubs cc2 ON cc2.club_id = p.club_id WHERE cc2.circuit_id = c.id AND cc2.club_confirmed = 1 AND cc2.circuit_confirmed = 1 AND p.confirmed = 1 AND p.deleted_at IS NULL) as player_count
    FROM circuits c
    WHERE c.confirmed = 1
    AND c.deleted_at IS NULL
    ORDER BY c.created_at DESC
    LIMIT 6
")->fetchAll();
?>

<section class="hero">
    <h1><?= __('hero_title') ?></h1>
    <p><?= __('hero_subtitle') ?></p>
    <div class="hero-buttons">
        <a href="?page=circuits" class="btn btn-primary"><?= __('hero_cta') ?></a>
        <a href="?page=create&highlight=circuit" class="btn btn-secondary" id="home-create-btn"><?= __('hero_create') ?></a>
    </div>
</section>

<section class="stats">
    <a href="?page=circuits" class="stat-card" style="text-decoration: none;">
        <div class="number"><?= $stats['circuits'] ?></div>
        <div class="label"><?= __('nav_circuits') ?></div>
    </a>
    <a href="?page=clubs" class="stat-card" style="text-decoration: none;">
        <div class="number"><?= $stats['clubs'] ?></div>
        <div class="label"><?= __('circuit_clubs') ?></div>
    </a>
    <a href="?page=players" class="stat-card" style="text-decoration: none;">
        <div class="number"><?= $stats['players'] ?></div>
        <div class="label"><?= __('circuit_players') ?></div>
    </a>
    <a href="?page=matches" class="stat-card" style="text-decoration: none;">
        <div class="number"><?= $stats['matches'] ?></div>
        <div class="label"><?= __('circuit_matches') ?></div>
    </a>
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
                <span><?= $circuit['club_count'] ?> <?= __('circuit_clubs') ?></span>
                <span><?= $circuit['player_count'] ?> <?= __('circuit_players') ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
