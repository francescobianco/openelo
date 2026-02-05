<?php
/**
 * OpenElo - Circuits List
 */

$db = Database::get();

// Get all confirmed circuits
$circuits = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.circuit_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as club_count,
        (SELECT COUNT(DISTINCT r.player_id) FROM ratings r WHERE r.circuit_id = c.id) as player_count,
        (SELECT COUNT(*) FROM matches m WHERE m.circuit_id = c.id AND m.rating_applied = 1) as match_count
    FROM circuits c
    WHERE c.confirmed = 1
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1><?= __('nav_circuits') ?></h1>
        <a href="?page=create&highlight=circuit" class="btn btn-primary"><?= __('hero_create') ?></a>
    </div>

    <?php if (empty($circuits)): ?>
    <div class="empty-state">
        <p><?= $lang === 'it' ? 'Nessun circuito ancora. Crea il primo!' : 'No circuits yet. Create the first one!' ?></p>
        <a href="?page=create&highlight=circuit" class="btn btn-primary"><?= __('hero_create') ?></a>
    </div>
    <?php else: ?>
    <div class="circuits-grid">
        <?php foreach ($circuits as $circuit): ?>
        <a href="?page=circuit&id=<?= $circuit['id'] ?>" class="circuit-card-link">
            <div class="circuit-card">
                <h3><?= htmlspecialchars($circuit['name']) ?></h3>
                <div class="circuit-meta">
                    <span><?= $circuit['club_count'] ?> <?= __('circuit_clubs') ?></span>
                    <span><?= $circuit['player_count'] ?> <?= __('circuit_players') ?></span>
                    <span><?= $circuit['match_count'] ?> <?= __('circuit_matches') ?></span>
                </div>
                <div class="circuit-date">
                    <?= date('d/m/Y', strtotime($circuit['created_at'])) ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
