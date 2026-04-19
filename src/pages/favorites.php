<?php
/**
 * OpenElo - Favorites Page
 */

$db = Database::get();

$raw  = urldecode($_COOKIE['openelo_favorites'] ?? '{}');
$favs = json_decode($raw, true) ?: [];

$favPlayerIds  = array_values(array_filter(array_map('intval', $favs['player']  ?? [])));
$favClubIds    = array_values(array_filter(array_map('intval', $favs['club']    ?? [])));
$favCircuitIds = array_values(array_filter(array_map('intval', $favs['circuit'] ?? [])));

$players = [];
if ($favPlayerIds) {
    $ph = implode(',', array_fill(0, count($favPlayerIds), '?'));
    $stmt = $db->prepare("SELECT p.*, c.name as club_name FROM players p JOIN clubs c ON c.id = p.club_id WHERE p.id IN ($ph) AND p.deleted_at IS NULL ORDER BY p.last_name, p.first_name");
    $stmt->execute($favPlayerIds);
    $players = $stmt->fetchAll();
}

$clubs = [];
if ($favClubIds) {
    $ph = implode(',', array_fill(0, count($favClubIds), '?'));
    $stmt = $db->prepare("SELECT * FROM clubs WHERE id IN ($ph) AND deleted_at IS NULL ORDER BY name");
    $stmt->execute($favClubIds);
    $clubs = $stmt->fetchAll();
}

$circuits = [];
if ($favCircuitIds) {
    $ph = implode(',', array_fill(0, count($favCircuitIds), '?'));
    $stmt = $db->prepare("SELECT * FROM circuits WHERE id IN ($ph) AND deleted_at IS NULL ORDER BY name");
    $stmt->execute($favCircuitIds);
    $circuits = $stmt->fetchAll();
}

$hasAny = !empty($players) || !empty($clubs) || !empty($circuits);
?>


<div class="container">
    <div class="page-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1><?= $lang === 'it' ? 'Preferiti' : 'Favorites' ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= $lang === 'it' ? 'Salvati su questo dispositivo' : 'Saved on this device' ?></span>
            </div>
        </div>
    </div>

    <?php if (!$hasAny): ?>
    <div class="card" style="text-align: center; padding: 3rem 2rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;">☆</div>
        <p style="color: var(--text-secondary); margin: 0;">
            <?= $lang === 'it'
                ? 'Nessun preferito ancora. Clicca la stellina ☆ su qualsiasi circuito, circolo o giocatore per aggiungerlo qui.'
                : 'No favorites yet. Click the ☆ star on any circuit, club, or player to save it here.' ?>
        </p>
    </div>
    <?php else: ?>

    <?php if (!empty($players)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h2 style="margin: 0 0 1rem 0; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary);">
            <?= $lang === 'it' ? 'Giocatori' : 'Players' ?>
        </h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($players as $i => $p):
                $isLast = $i === array_key_last($players);
            ?>
            <li style="padding: 0.6rem 0; <?= !$isLast ? 'border-bottom: 1px solid var(--border);' : '' ?> display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <div>
                    <a href="?page=player&id=<?= $p['id'] ?>" style="font-weight: 500;"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></a>
                    <span style="font-size: 0.85rem; color: var(--text-secondary); margin-left: 0.5rem;"><?= htmlspecialchars($p['club_name']) ?></span>
                </div>
                <button class="btn-star fav-active" data-fav-type="player" data-fav-id="<?= $p['id'] ?>" onclick="toggleFavorite('player', <?= $p['id'] ?>)" title="<?= $lang === 'it' ? 'Rimuovi dai preferiti' : 'Remove from favorites' ?>">★</button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($clubs)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h2 style="margin: 0 0 1rem 0; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary);">
            <?= $lang === 'it' ? 'Circoli' : 'Clubs' ?>
        </h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($clubs as $i => $c):
                $isLast = $i === array_key_last($clubs);
            ?>
            <li style="padding: 0.6rem 0; <?= !$isLast ? 'border-bottom: 1px solid var(--border);' : '' ?> display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <a href="?page=club&id=<?= $c['id'] ?>" style="font-weight: 500;"><?= htmlspecialchars($c['name']) ?></a>
                <button class="btn-star fav-active" data-fav-type="club" data-fav-id="<?= $c['id'] ?>" onclick="toggleFavorite('club', <?= $c['id'] ?>)" title="<?= $lang === 'it' ? 'Rimuovi dai preferiti' : 'Remove from favorites' ?>">★</button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($circuits)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h2 style="margin: 0 0 1rem 0; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary);">
            <?= $lang === 'it' ? 'Circuiti' : 'Circuits' ?>
        </h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($circuits as $i => $c):
                $isLast = $i === array_key_last($circuits);
            ?>
            <li style="padding: 0.6rem 0; <?= !$isLast ? 'border-bottom: 1px solid var(--border);' : '' ?> display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <a href="?page=circuit&id=<?= $c['id'] ?>" style="font-weight: 500;"><?= htmlspecialchars($c['name']) ?></a>
                <button class="btn-star fav-active" data-fav-type="circuit" data-fav-id="<?= $c['id'] ?>" onclick="toggleFavorite('circuit', <?= $c['id'] ?>)" title="<?= $lang === 'it' ? 'Rimuovi dai preferiti' : 'Remove from favorites' ?>">★</button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
