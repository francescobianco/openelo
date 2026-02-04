<?php
/**
 * OpenELO - Clubs List
 */

$db = Database::get();

// Get all active clubs (president confirmed + at least one active circuit)
$clubs = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as circuit_count,
        (SELECT COUNT(*) FROM players p WHERE p.club_id = c.id AND p.confirmed = 1) as player_count,
        (SELECT GROUP_CONCAT(ci.name, ', ') FROM circuit_clubs cc2
         JOIN circuits ci ON ci.id = cc2.circuit_id
         WHERE cc2.club_id = c.id AND cc2.club_confirmed = 1 AND cc2.circuit_confirmed = 1) as circuit_names
    FROM clubs c
    WHERE c.president_confirmed = 1
    AND EXISTS (
        SELECT 1 FROM circuit_clubs cc
        WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    )
    ORDER BY c.name
")->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1><?= __('nav_clubs') ?></h1>
        <a href="?page=create" class="btn btn-primary"><?= __('club_create') ?></a>
    </div>

    <?php if (empty($clubs)): ?>
    <div class="empty-state">
        <p><?= $lang === 'it' ? 'Nessun circolo ancora. Crea il primo!' : 'No clubs yet. Create the first one!' ?></p>
        <a href="?page=create" class="btn btn-primary"><?= __('club_create') ?></a>
    </div>
    <?php else: ?>
    <div class="circuits-grid">
        <?php foreach ($clubs as $club): ?>
        <a href="?page=club&id=<?= $club['id'] ?>" class="circuit-card-link">
            <div class="circuit-card">
                <h3><?= htmlspecialchars($club['name']) ?></h3>
                <div class="circuit-meta">
                    <span>♔ <?= $club['circuit_count'] ?> <?= __('nav_circuits') ?></span>
                    <span>♟ <?= $club['player_count'] ?> <?= __('circuit_players') ?></span>
                </div>
                <?php if ($club['circuit_names']): ?>
                <div class="club-circuits">
                    <?= htmlspecialchars($club['circuit_names']) ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
