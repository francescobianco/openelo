<?php
/**
 * OpenElo - Formulas & Circuits Guide
 */
?>

<div class="container">
    <div class="page-header">
        <h1><?= $lang === 'it' ? 'Formule e Circuiti' : 'Formulas & Circuits' ?></h1>
    </div>

    <?php if ($lang === 'it'): ?>

    <div class="card" style="margin-bottom: 1rem;">
        <p style="margin: 0; color: var(--text-secondary); line-height: 1.7;">
            OpenELO supporta diversi sistemi di classifica, ognuno pensato per un tipo diverso di competizione.
            In questa pagina trovi la spiegazione dettagliata di ogni formula disponibile e alcuni consigli su quando usarla.
        </p>
    </div>

    <nav style="margin-bottom: 2rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <a href="#classic-elo" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">ELO Classico</a>
        <a href="#mobile-ranking" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Classifica Mobile</a>
        <a href="#ladder-3up-sliding" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Scaletta Scorrevole</a>
        <a href="#ladder-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Scaletta Senza Pareggio</a>
        <a href="#knockout-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Eliminazione Diretta</a>
    </nav>

    <section id="classic-elo" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">ELO Classico</h2>
            <p style="text-align: justify; line-height: 1.7;">
                La formula ELO classica assegna ad ogni giocatore un punteggio numerico che rappresenta la sua forza relativa.
                Ogni partita aggiorna i rating di entrambi i giocatori in base al risultato e alla differenza di rating tra loro.
                Battere un avversario molto più forte vale molto; battere un avversario molto più debole vale poco.
            </p>
            <h3 style="font-size: 1rem;">Come funziona</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Il sistema usa fattori K in stile FIDE: <strong>K=40</strong> per i nuovi giocatori (prime 30 partite),
                <strong>K=20</strong> per i giocatori con rating sotto 2200, <strong>K=10</strong> per i giocatori con rating
                uguale o superiore a 2200. Il rating di partenza è <?= ELO_START ?>.
            </p>
            <h3 style="font-size: 1rem;">Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideale per circuiti di allenamento aperti, dove ogni partita conta indipendentemente dall'avversario scelto.
                È il sistema usato dalla maggior parte delle federazioni scacchistiche.
            </p>
        </div>
    </section>

    <section id="mobile-ranking" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Classifica Mobile</h2>
            <p style="text-align: justify; line-height: 1.7;">
                La Classifica Mobile è un sistema basato sulla posizione: ogni giocatore occupa un posto nella classifica
                e le partite fanno scorrere i giocatori verso l'alto o verso il basso.
                Non esiste un punteggio numerico ufficiale — conta solo la posizione.
            </p>
            <h3 style="font-size: 1rem;">Vittoria</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Se batti un avversario classificato più in alto, prendi il suo posto esatto e tutti i giocatori
                che si trovavano tra te e lui scorrono di una posizione verso il basso.
                Se vince il giocatore già più in alto, le posizioni non cambiano.
            </p>
            <h3 style="font-size: 1rem;">Pareggio</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Anche i pareggi hanno effetto sulla classifica:
            </p>
            <ul style="line-height: 1.9;">
                <li>Pareggi contro un avversario classificato <strong>più in alto</strong>: sali di una posizione, il giocatore scalzato scende di uno.</li>
                <li>Pareggi contro un avversario di <strong>pari livello o più in basso</strong>: nessun cambiamento.</li>
                <li>Se entrambi i giocatori sono <strong>non ancora classificati</strong> e pareggiano: il giocatore con il nero entra in posizione 1, quello con il bianco in posizione 2, tutti gli altri scorrono di due posizioni verso il basso.</li>
            </ul>
            <h3 style="font-size: 1rem;">Nuovi giocatori</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Tutti i giocatori del circuito compaiono in classifica dal primo giorno, anche prima di aver giocato la loro prima partita,
                in fondo alla lista. Questo evita che la classifica rimanga vuota all'avvio del circuito.
            </p>
            <h3 style="font-size: 1rem;">Rating ELO</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Il rating ELO viene calcolato in parallelo solo a fini statistici e di analisi.
                Non influisce sulla posizione ufficiale, che è determinata esclusivamente dai risultati delle partite.
            </p>
            <h3 style="font-size: 1rem;">Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Adatta a circuiti informali di circolo dove si vuole una classifica viva e dinamica,
                facile da leggere e motivante per tutti i livelli.
            </p>
        </div>
    </section>

    <section id="ladder-3up-sliding" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Scaletta Scorrevole (±3 posizioni)</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Una scaletta in cui puoi sfidare solo giocatori entro 3 posizioni sopra di te.
                Se vinci, prendi il loro posto esatto e tutti i giocatori intermedi scorrono di una posizione verso il basso.
                Se perdi, le posizioni restano invariate.
            </p>
            <h3 style="font-size: 1rem;">Pareggi</h3>
            <p style="text-align: justify; line-height: 1.7;">
                I pareggi non modificano le posizioni in questa formula.
            </p>
            <h3 style="font-size: 1rem;">Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideale quando si vuole una progressione controllata: nessuno può saltare troppi posti in un colpo solo,
                e la classifica riflette una gerarchia più stabile.
            </p>
        </div>
    </section>

    <section id="ladder-no-draw" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Scaletta Senza Pareggio</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Una scaletta classica in cui i pareggi non sono ammessi — ogni partita deve avere un vincitore.
                Chi vince prende il posto dell'avversario se era classificato più in alto.
            </p>
            <h3 style="font-size: 1rem;">Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Adatta quando si vuole eliminare l'ambiguità del pareggio e garantire sempre un vincitore chiaro,
                ad esempio in varianti di gioco con regole speciali come l'Armageddon.
            </p>
        </div>
    </section>

    <section id="knockout-no-draw" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Eliminazione Diretta</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Sistema a eliminazione diretta: chi perde è eliminato, chi vince avanza.
                I pareggi non sono ammessi — ogni partita deve produrre un vincitore netto.
            </p>
            <h3 style="font-size: 1rem;">Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Perfetta per mini-tornei interni, sfide dirette a eliminazione, o circuiti brevi dove si vuole
                una struttura competitiva semplice e immediata.
            </p>
        </div>
    </section>

    <?php else: ?>

    <div class="card" style="margin-bottom: 1rem;">
        <p style="margin: 0; color: var(--text-secondary); line-height: 1.7;">
            OpenELO supports several ranking systems, each designed for a different type of competition.
            This page explains each available formula in detail and offers guidance on when to use it.
        </p>
    </div>

    <nav style="margin-bottom: 2rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <a href="#classic-elo" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Classic ELO</a>
        <a href="#mobile-ranking" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Mobile Ranking</a>
        <a href="#ladder-3up-sliding" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Sliding Ladder</a>
        <a href="#ladder-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">No-Draw Ladder</a>
        <a href="#knockout-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Knockout</a>
    </nav>

    <section id="classic-elo" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Classic ELO</h2>
            <p style="text-align: justify; line-height: 1.7;">
                The classic ELO formula assigns each player a numerical score representing their relative strength.
                Every match updates both players' ratings based on the result and the rating gap between them.
                Beating a much stronger opponent earns a lot; beating a much weaker one earns little.
            </p>
            <h3 style="font-size: 1rem;">How it works</h3>
            <p style="text-align: justify; line-height: 1.7;">
                The system uses FIDE-style K factors: <strong>K=40</strong> for new players (first 30 games),
                <strong>K=20</strong> for players rated below 2200, <strong>K=10</strong> for players rated
                2200 or above. Starting rating is <?= ELO_START ?>.
            </p>
            <h3 style="font-size: 1rem;">When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideal for open training circuits where every game counts regardless of who you choose to play.
                It is the system used by most chess federations worldwide.
            </p>
        </div>
    </section>

    <section id="mobile-ranking" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Mobile Ranking</h2>
            <p style="text-align: justify; line-height: 1.7;">
                The Mobile Ranking is a position-based system: each player holds a spot on the leaderboard
                and matches push players up or down. There is no numerical score — only position matters.
            </p>
            <h3 style="font-size: 1rem;">Win</h3>
            <p style="text-align: justify; line-height: 1.7;">
                If you beat a higher-ranked opponent, you take their exact spot and all players between you
                and them slide down one position. If the higher-ranked player wins, positions stay unchanged.
            </p>
            <h3 style="font-size: 1rem;">Draw</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Draws also affect the ranking:
            </p>
            <ul style="line-height: 1.9;">
                <li>Draw against a <strong>higher-ranked</strong> opponent: you move up one position, the displaced player slides down one.</li>
                <li>Draw against an <strong>equal or lower-ranked</strong> opponent: no change.</li>
                <li>If both players are <strong>unranked</strong> and draw: the black player enters at position 1, the white player at position 2, and everyone else slides down two spots.</li>
            </ul>
            <h3 style="font-size: 1rem;">New players</h3>
            <p style="text-align: justify; line-height: 1.7;">
                All circuit players appear in the ranking from day one, even before their first match,
                placed at the bottom of the list. This ensures the ranking is never empty at the start of a circuit.
            </p>
            <h3 style="font-size: 1rem;">ELO rating</h3>
            <p style="text-align: justify; line-height: 1.7;">
                An ELO rating is calculated in parallel for statistical and analytical purposes only.
                It does not affect the official standing, which is determined solely by match results.
            </p>
            <h3 style="font-size: 1rem;">When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Great for informal club circuits where you want a lively, dynamic leaderboard
                that is easy to read and motivating for players of all levels.
            </p>
        </div>
    </section>

    <section id="ladder-3up-sliding" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Sliding Ladder (±3 positions)</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A ladder where you may only challenge players within 3 positions above you.
                If you win, you take their exact spot and all players in between slide down one position.
                If you lose, positions remain unchanged.
            </p>
            <h3 style="font-size: 1rem;">Draws</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Draws do not modify positions in this formula.
            </p>
            <h3 style="font-size: 1rem;">When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideal when you want controlled progression: no one can leap too many spots at once,
                and the ranking reflects a more stable hierarchy.
            </p>
        </div>
    </section>

    <section id="ladder-no-draw" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">No-Draw Ladder</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A classic ladder where draws are not allowed — every match must have a winner.
                The winner takes the loser's spot if the loser was ranked higher.
            </p>
            <h3 style="font-size: 1rem;">When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Suited for circuits where you want to eliminate draw ambiguity and always guarantee a clear winner,
                for example in special game variants like Armageddon.
            </p>
        </div>
    </section>

    <section id="knockout-no-draw" style="margin-bottom: 2.5rem; scroll-margin-top: 5rem;">
        <div class="card">
            <h2 style="margin-top: 0;">Knockout (No Draw)</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A knockout elimination system: losers are out, winners advance.
                Draws are not allowed — every match must produce a clear winner.
            </p>
            <h3 style="font-size: 1rem;">When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Perfect for internal mini-tournaments, direct knockout challenges, or short circuits where
                you want a simple and immediate competitive structure.
            </p>
        </div>
    </section>

    <?php endif; ?>
</div>
