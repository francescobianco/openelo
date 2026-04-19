<?php
/**
 * OpenElo - Formulas & Circuits Guide
 */
?>

<div class="container">
    <div class="page-header">
        <h1><?= $lang === 'it' ? 'Formule e Circuiti' : 'Formulas & Circuits' ?></h1>
    </div>

    <p style="color: var(--text-secondary); line-height: 1.7; margin-bottom: 1.5rem; text-align: justify;">
        <?= $lang === 'it'
            ? 'OpenELO supporta diversi sistemi di classifica, ognuno pensato per un tipo diverso di competizione. Qui trovi la spiegazione dettagliata di ogni formula disponibile e alcuni consigli su quando usarla.'
            : 'OpenELO supports several ranking systems, each designed for a different type of competition. Here you will find a detailed explanation of each available formula and guidance on when to use it.' ?>
    </p>

    <nav style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 2rem;">
        <a href="#classic-elo" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;"><?= $lang === 'it' ? 'ELO Classico' : 'Classic ELO' ?></a>
        <a href="#mobile-ranking" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;"><?= $lang === 'it' ? 'Classifica Mobile' : 'Mobile Ranking' ?></a>
        <a href="#ladder-3up-sliding" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;"><?= $lang === 'it' ? 'Scaletta Scorrevole' : 'Sliding Ladder' ?></a>
        <a href="#ladder-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;"><?= $lang === 'it' ? 'Scaletta Senza Pareggio' : 'No-Draw Ladder' ?></a>
        <a href="#knockout-no-draw" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;"><?= $lang === 'it' ? 'Eliminazione Diretta' : 'Knockout' ?></a>
    </nav>

    <div class="card">

        <?php if ($lang === 'it'): ?>

        <section id="classic-elo" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ ELO Classico</h2>
            <p style="text-align: justify; line-height: 1.7;">
                La formula ELO classica assegna ad ogni giocatore un punteggio numerico che rappresenta la sua forza relativa.
                Ogni partita aggiorna i rating di entrambi i giocatori in base al risultato e alla differenza di rating tra loro:
                battere un avversario molto più forte vale molto, battere uno molto più debole vale poco.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ Come funziona</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Il sistema usa fattori K in stile FIDE: <strong>K=40</strong> per i nuovi giocatori (prime 30 partite),
                <strong>K=20</strong> per i giocatori con rating sotto 2200, <strong>K=10</strong> sopra 2200.
                Il rating di partenza è <?= ELO_START ?>.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Pareggi</h3>
            <p style="text-align: justify; line-height: 1.7;">
                I pareggi modificano il rating di entrambi i giocatori in proporzione alla differenza attesa:
                pareggiare contro un avversario più forte fa guadagnare punti, contro uno più debole fa perdere punti.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📈 Progressione</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Non esistono restrizioni su chi sfidare: si può giocare contro chiunque nel circuito.
                Più partite si giocano, più il rating converge verso il livello reale del giocatore.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideale per circuiti di allenamento aperti dove ogni partita conta indipendentemente dall'avversario scelto.
                È il sistema usato dalla maggior parte delle federazioni scacchistiche mondiali.
            </p>
        </section>

        <section id="mobile-ranking" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Classifica Mobile</h2>
            <p style="text-align: justify; line-height: 1.7;">
                La Classifica Mobile è un sistema basato sulla posizione: ogni giocatore occupa un posto nella classifica
                e le partite lo fanno scorrere verso l'alto o verso il basso.
                Non esiste un punteggio numerico ufficiale — conta solo la posizione.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">♟️ Vittoria</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Se batti un avversario classificato più in alto, prendi il suo posto esatto e tutti i giocatori
                tra te e lui scorrono di una posizione verso il basso.
                Se vince il giocatore già più in alto, le posizioni non cambiano.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Pareggio</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Anche i pareggi hanno effetto sulla classifica. Pareggiare contro un avversario classificato
                <strong>più in alto</strong> ti fa salire di una posizione; il giocatore scalzato scende di uno.
                Pareggiare contro un avversario di pari livello o più in basso non produce alcun effetto.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🆕 Nuovi giocatori</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Tutti i giocatori del circuito compaiono in classifica dal primo giorno, anche prima di aver giocato
                la loro prima partita, posizionati in fondo alla lista.
                Questo evita che la classifica rimanga vuota all'avvio del circuito e motiva fin da subito a giocare.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📊 Rating ELO</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Un rating ELO viene calcolato in parallelo solo a fini statistici e di analisi.
                Non influisce sulla posizione ufficiale, che è determinata esclusivamente dai risultati delle partite.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Adatta a circuiti informali di circolo dove si vuole una classifica viva e dinamica,
                facile da leggere e motivante per tutti i livelli di gioco.
            </p>
        </section>

        <section id="ladder-3up-sliding" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Scaletta Scorrevole</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Una scaletta in cui puoi sfidare solo giocatori entro 3 posizioni sopra di te.
                Se vinci, prendi il loro posto esatto e tutti i giocatori intermedi scorrono di una posizione verso il basso.
                Se perdi, le posizioni restano invariate.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ Come funziona</h3>
            <p style="text-align: justify; line-height: 1.7;">
                La regola delle 3 posizioni limita i salti improvvisi in classifica: non puoi sfidare il primo
                della classifica se sei al decimo posto. Questo rende la progressione più graduale e realistica.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Pareggio</h3>
            <p style="text-align: justify; line-height: 1.7;">
                I pareggi non modificano le posizioni in questa formula.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📈 Progressione</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Per scalare tutta la classifica è necessario vincere più partite successive, una fascia alla volta.
                Chi è in cima deve difendersi solo dagli avversari immediatamente sotto di lui.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideale quando si vuole una progressione controllata e una gerarchia più stabile,
                dove nessuno può scalare troppi posti in un colpo solo.
            </p>
        </section>

        <section id="ladder-no-draw" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Scaletta Senza Pareggio</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Una scaletta classica in cui i pareggi non sono ammessi: ogni partita deve avere un vincitore.
                Chi vince prende il posto dell'avversario se era classificato più in alto.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ Come funziona</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Funziona come una scaletta standard, ma il regolamento richiede che ogni partita produca
                un risultato netto. I pareggi non vengono registrati: è compito dei giocatori accordarsi
                su un formato di spareggio (blitz, Armageddon, ecc.).
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">❌ Nessun pareggio</h3>
            <p style="text-align: justify; line-height: 1.7;">
                La formula non accetta il risultato di pareggio. Ogni incontro deve concludersi con una vittoria netta.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Adatta a varianti di gioco che non prevedono il pareggio, o a circuiti che vogliono
                eliminare l'ambiguità del risultato e garantire sempre un vincitore chiaro.
            </p>
        </section>

        <section id="knockout-no-draw" style="scroll-margin-top: 5rem;">
            <h2 style="margin-top: 0;">⚖️ Eliminazione Diretta</h2>
            <p style="text-align: justify; line-height: 1.7;">
                Sistema a eliminazione diretta: chi perde è eliminato, chi vince avanza.
                I pareggi non sono ammessi — ogni partita deve produrre un vincitore netto.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ Come funziona</h3>
            <p style="text-align: justify; line-height: 1.7;">
                I giocatori si sfidano in scontri diretti a eliminazione. Una sconfitta equivale all'uscita dal torneo.
                Il circuito termina quando rimane un solo giocatore imbattuto.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">❌ Nessun pareggio</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Come nella scaletta senza pareggio, ogni incontro deve produrre un risultato netto.
                I giocatori devono accordarsi su un formato che garantisca un vincitore.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 Quando usarla</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Perfetta per mini-tornei interni, sfide dirette a eliminazione, o circuiti brevi dove si vuole
                una struttura competitiva semplice, immediata e ad alta tensione.
            </p>
        </section>

        <?php else: ?>

        <section id="classic-elo" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Classic ELO</h2>
            <p style="text-align: justify; line-height: 1.7;">
                The classic ELO formula assigns each player a numerical score representing their relative strength.
                Every match updates both players' ratings based on the result and the rating gap between them:
                beating a much stronger opponent earns a lot, beating a much weaker one earns little.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ How it works</h3>
            <p style="text-align: justify; line-height: 1.7;">
                The system uses FIDE-style K factors: <strong>K=40</strong> for new players (first 30 games),
                <strong>K=20</strong> for players rated below 2200, <strong>K=10</strong> above 2200.
                Starting rating is <?= ELO_START ?>.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Draws</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Draws update both players' ratings in proportion to the expected outcome:
                drawing against a stronger opponent gains points, drawing against a weaker one loses points.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📈 Progression</h3>
            <p style="text-align: justify; line-height: 1.7;">
                There are no restrictions on who to challenge: you can play anyone in the circuit.
                The more games played, the more the rating converges toward the player's true level.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideal for open training circuits where every game counts regardless of who you choose to play.
                It is the system used by most chess federations worldwide.
            </p>
        </section>

        <section id="mobile-ranking" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Mobile Ranking</h2>
            <p style="text-align: justify; line-height: 1.7;">
                The Mobile Ranking is a position-based system: each player holds a spot on the leaderboard
                and matches push players up or down. There is no numerical score — only position matters.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">♟️ Win</h3>
            <p style="text-align: justify; line-height: 1.7;">
                If you beat a higher-ranked opponent, you take their exact spot and all players between you
                and them slide down one position.
                If the higher-ranked player wins, positions stay unchanged.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Draw</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Draws also affect the ranking. Drawing against a <strong>higher-ranked</strong> opponent
                moves you up one position; the displaced player slides down one.
                Drawing against an equal or lower-ranked opponent has no effect.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🆕 New players</h3>
            <p style="text-align: justify; line-height: 1.7;">
                All circuit players appear in the ranking from day one, even before their first match,
                placed at the bottom of the list. This ensures the ranking is never empty at the start
                of a circuit and gives everyone an immediate incentive to play.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📊 ELO Rating</h3>
            <p style="text-align: justify; line-height: 1.7;">
                An ELO rating is calculated in parallel for statistical and analytical purposes only.
                It does not affect the official standing, which is determined solely by match results.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Great for informal club circuits where you want a lively, dynamic leaderboard
                that is easy to read and motivating for players of all levels.
            </p>
        </section>

        <section id="ladder-3up-sliding" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ Sliding Ladder</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A ladder where you may only challenge players within 3 positions above you.
                If you win, you take their exact spot and all players in between slide down one position.
                If you lose, positions remain unchanged.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ How it works</h3>
            <p style="text-align: justify; line-height: 1.7;">
                The 3-position rule prevents sudden leaps up the leaderboard: you cannot challenge the
                top player if you are ranked tenth. This makes progression gradual and realistic.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🤝 Draw</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Draws do not modify positions in this formula.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">📈 Progression</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Climbing the entire ladder requires winning multiple successive matches, one band at a time.
                Those at the top only need to defend against players immediately below them.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Ideal when you want controlled progression and a more stable hierarchy,
                where no one can leap too many spots at once.
            </p>
        </section>

        <section id="ladder-no-draw" style="scroll-margin-top: 5rem; padding-bottom: 2rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border);">
            <h2 style="margin-top: 0;">⚖️ No-Draw Ladder</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A classic ladder where draws are not allowed — every match must have a winner.
                The winner takes the loser's spot if the loser was ranked higher.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ How it works</h3>
            <p style="text-align: justify; line-height: 1.7;">
                It works like a standard ladder, but the rules require every match to produce a clear result.
                Draws are not recorded: players must agree on a tiebreak format (blitz, Armageddon, etc.).
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">❌ No draws</h3>
            <p style="text-align: justify; line-height: 1.7;">
                The formula does not accept a draw result. Every game must end with a decisive winner.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Suited for game variants that do not allow draws, or for circuits that want to eliminate
                result ambiguity and always guarantee a clear winner.
            </p>
        </section>

        <section id="knockout-no-draw" style="scroll-margin-top: 5rem;">
            <h2 style="margin-top: 0;">⚖️ Knockout</h2>
            <p style="text-align: justify; line-height: 1.7;">
                A knockout elimination system: losers are out, winners advance.
                Draws are not allowed — every match must produce a clear winner.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">⚙️ How it works</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Players face each other in direct elimination matches. A loss means elimination from the circuit.
                The circuit ends when only one undefeated player remains.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">❌ No draws</h3>
            <p style="text-align: justify; line-height: 1.7;">
                As with the no-draw ladder, every match must produce a decisive result.
                Players must agree on a format that guarantees a winner.
            </p>
            <h3 style="font-size: 0.95rem; margin-top: 1.5rem;">🎯 When to use it</h3>
            <p style="text-align: justify; line-height: 1.7;">
                Perfect for internal mini-tournaments, direct knockout challenges, or short circuits where
                you want a simple, immediate, and high-stakes competitive structure.
            </p>
        </section>

        <?php endif; ?>

    </div>
</div>
