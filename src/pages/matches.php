<?php
/**
 * OpenElo - Matches List
 */

$db = Database::get();
require_once SRC_PATH . '/utils.php';

$matches = $db->query("
    SELECT m.*,
        ci.name as circuit_name, ci.id as circuit_id,
        pw.first_name as white_first, pw.last_name as white_last, pw.id as white_id,
        pb.first_name as black_first, pb.last_name as black_last, pb.id as black_id,
        cw.protected_mode as white_club_protected, cw.id as white_club_id,
        cb.protected_mode as black_club_protected, cb.id as black_club_id
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    JOIN clubs cw ON cw.id = pw.club_id
    JOIN clubs cb ON cb.id = pb.club_id
    WHERE m.deleted_at IS NULL
    AND ci.deleted_at IS NULL
    AND pw.deleted_at IS NULL
    AND pb.deleted_at IS NULL
    AND cw.deleted_at IS NULL
    AND cb.deleted_at IS NULL
    ORDER BY m.created_at DESC
    LIMIT 200
")->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h1><?= $lang === 'it' ? 'Partite' : 'Matches' ?></h1>
    </div>

    <?php if (empty($matches)): ?>
    <div class="empty-state">
        <p><?= $lang === 'it' ? 'Nessuna partita ancora.' : 'No matches yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'it' ? 'Data' : 'Date' ?></th>
                        <th><?= $lang === 'it' ? 'Bianco' : 'White' ?></th>
                        <th><?= $lang === 'it' ? 'Nero' : 'Black' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                        <th><?= __('form_circuit') ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Stato' : 'Status' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $m): ?>
                    <tr>
                        <td style="color: var(--text-secondary); font-size: 0.9rem; white-space: nowrap;">
                            <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                        </td>
                        <td>
                            <?php $canViewWhite = !$m['white_club_protected'] || hasClubAccess((int)$m['white_club_id']); ?>
                            <?php if ($canViewWhite): ?>
                            <a href="?page=player&id=<?= $m['white_id'] ?>"><?= htmlspecialchars($m['white_first'] . ' ' . $m['white_last']) ?></a>
                            <?php else: ?>
                            <a href="?page=player&id=<?= $m['white_id'] ?>" style="color: var(--text-secondary);"><?= maskName($m['white_first'] . ' ' . $m['white_last']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $canViewBlack = !$m['black_club_protected'] || hasClubAccess((int)$m['black_club_id']); ?>
                            <?php if ($canViewBlack): ?>
                            <a href="?page=player&id=<?= $m['black_id'] ?>"><?= htmlspecialchars($m['black_first'] . ' ' . $m['black_last']) ?></a>
                            <?php else: ?>
                            <a href="?page=player&id=<?= $m['black_id'] ?>" style="color: var(--text-secondary);"><?= maskName($m['black_first'] . ' ' . $m['black_last']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 1.05rem; white-space: nowrap;">
                            <?= htmlspecialchars(str_replace('-', ' - ', $m['result'])) ?>
                        </td>
                        <td>
                            <a href="?page=circuit&id=<?= $m['circuit_id'] ?>"><?= htmlspecialchars($m['circuit_name']) ?></a>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($m['rating_applied']): ?>
                            <span class="badge badge-success"><?= $lang === 'it' ? 'Confermata' : 'Confirmed' ?></span>
                            <?php else: ?>
                            <span class="badge badge-warning"><?= $lang === 'it' ? 'In attesa' : 'Pending' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?page=match&id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">
                                <?= $lang === 'it' ? 'Vedi partita' : 'View match' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
