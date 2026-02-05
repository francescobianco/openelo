<?php
/**
 * OpenELO - Players List
 */

$db = Database::get();

// Get all confirmed players with their club and best rating
$players = $db->query("
    SELECT p.*, c.name as club_name, c.id as club_id,
        (SELECT MAX(r.rating) FROM ratings r WHERE r.player_id = p.id) as best_rating,
        (SELECT SUM(r.games_played) FROM ratings r WHERE r.player_id = p.id) as total_games
    FROM players p
    JOIN clubs c ON c.id = p.club_id
    WHERE p.confirmed = 1
    ORDER BY p.last_name, p.first_name
")->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1><?= __('nav_players') ?></h1>
    </div>

    <?php if (empty($players)): ?>
    <div class="empty-state">
        <p><?= $lang === 'it' ? 'Nessun giocatore ancora.' : 'No players yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'it' ? 'Giocatore' : 'Player' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Categoria' : 'Category' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Miglior Rating' : 'Best Rating' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Partite' : 'Games' ?></th>
                        <th><?= $lang === 'it' ? 'Circolo' : 'Club' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $p): ?>
                    <tr>
                        <td>
                            <a href="?page=player&id=<?= $p['id'] ?>"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></a>
                        </td>
                        <td style="text-align: center;"><strong><?= htmlspecialchars($p['category'] ?? 'NC') ?></strong></td>
                        <td style="text-align: center;" class="rating"><?= $p['best_rating'] ?? '-' ?></td>
                        <td style="text-align: center;"><?= $p['total_games'] ?? 0 ?></td>
                        <td>
                            <a href="?page=club&id=<?= $p['club_id'] ?>"><?= htmlspecialchars($p['club_name']) ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
