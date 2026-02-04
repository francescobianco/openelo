<?php
/**
 * OpenElo - About Page
 */
?>

<div class="container">
    <?php if ($lang === 'it'): ?>
    <!-- Italian Version -->
    <div class="page-header">
        <h1>♞ Chi Siamo</h1>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto;">
        <h2>Cos'è OpenELO?</h2>
        <p>
            OpenELO è una piattaforma open-source per la gestione di classifiche e rating degli scacchi basati sul sistema ELO.
            Permette a club, circuiti e associazioni di organizzare tornei e mantenere classifiche aggiornate in modo semplice e trasparente.
        </p>

        <h2 style="margin-top: 2rem;">Come Funziona il Sistema ELO</h2>
        <p>
            Il sistema ELO è un metodo per calcolare il livello relativo di abilità dei giocatori in giochi competitivi come gli scacchi.
            Sviluppato da Arpad Elo, questo sistema assegna un punteggio numerico a ciascun giocatore che aumenta o diminuisce in base ai risultati delle partite.
        </p>
        <ul style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary);">
            <li>Vincere contro un avversario più forte fa guadagnare più punti</li>
            <li>Perdere contro un avversario più debole fa perdere più punti</li>
            <li>Un pareggio è valutato in base alla differenza di rating</li>
        </ul>

        <h2 style="margin-top: 2rem;">A Chi Serve</h2>
        <p>OpenELO è pensato per:</p>
        <ul style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary);">
            <li><strong>Circuiti scacchistici</strong> - Gestire classifiche multiple tra club affiliati</li>
            <li><strong>Circoli scacchistici</strong> - Organizzare tornei interni e tenere traccia dei progressi dei giocatori</li>
            <li><strong>Giocatori</strong> - Monitorare il proprio rating e vedere lo storico delle partite</li>
            <li><strong>Organizzatori</strong> - Gestire eventi e validare risultati in modo trasparente</li>
        </ul>

        <h2 style="margin-top: 2rem;">Caratteristiche Principali</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 1.5rem 0;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">📊 Classifiche Real-time</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Rating aggiornati automaticamente dopo ogni partita confermata
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">✓ Sistema di Approvazione</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Doppia o tripla conferma per garantire l'accuratezza dei risultati
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🏆 Categorie</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Supporto per categorie FIDE (GM, IM, FM, CM) e nazionali
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">📈 Storico Dettagliato</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Visualizza tutte le partite e le variazioni di rating nel tempo
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🔄 Trasferimenti</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Gestione dei trasferimenti tra circoli con approvazione
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🔓 Open Source</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Codice completamente aperto e modificabile su GitHub
                </p>
            </div>
        </div>

        <h2 style="margin-top: 2rem;">Come Iniziare</h2>
        <ol style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary); line-height: 1.8;">
            <li><strong>Crea un circuito</strong> - Registra il tuo circuito scacchistico</li>
            <li><strong>Registra circoli</strong> - I circoli possono aderire al circuito</li>
            <li><strong>Aggiungi giocatori</strong> - I giocatori si registrano nei circoli</li>
            <li><strong>Inserisci risultati</strong> - Registra le partite e conferma i risultati</li>
            <li><strong>Monitora le classifiche</strong> - Segui i progressi in tempo reale</li>
        </ol>

        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; text-align: center;">
            <p style="margin: 0 0 1rem 0; color: var(--text-secondary);">
                Pronto per iniziare?
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="?page=create" class="btn btn-primary">Crea Circuito</a>
                <a href="?page=contact" class="btn btn-secondary">Contattaci</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- English Version -->
    <div class="page-header">
        <h1>♞ About OpenELO</h1>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto;">
        <h2>What is OpenELO?</h2>
        <p>
            OpenELO is an open-source platform for managing chess rankings and ratings based on the ELO system.
            It allows clubs, circuits, and associations to organize tournaments and maintain updated rankings in a simple and transparent way.
        </p>

        <h2 style="margin-top: 2rem;">How the ELO System Works</h2>
        <p>
            The ELO system is a method for calculating the relative skill levels of players in competitive games like chess.
            Developed by Arpad Elo, this system assigns a numerical score to each player that increases or decreases based on match results.
        </p>
        <ul style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary);">
            <li>Winning against a stronger opponent earns more points</li>
            <li>Losing against a weaker opponent loses more points</li>
            <li>A draw is evaluated based on the rating difference</li>
        </ul>

        <h2 style="margin-top: 2rem;">Who It's For</h2>
        <p>OpenELO is designed for:</p>
        <ul style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary);">
            <li><strong>Chess circuits</strong> - Manage multiple rankings across affiliated clubs</li>
            <li><strong>Chess clubs</strong> - Organize internal tournaments and track player progress</li>
            <li><strong>Players</strong> - Monitor your rating and view match history</li>
            <li><strong>Organizers</strong> - Manage events and validate results transparently</li>
        </ul>

        <h2 style="margin-top: 2rem;">Key Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 1.5rem 0;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">📊 Real-time Rankings</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Ratings automatically updated after each confirmed match
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">✓ Approval System</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Double or triple confirmation to ensure result accuracy
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🏆 Categories</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Support for FIDE categories (GM, IM, FM, CM) and national levels
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">📈 Detailed History</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    View all matches and rating changes over time
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🔄 Transfers</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Club transfer management with approval process
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">🔓 Open Source</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Fully open and modifiable code on GitHub
                </p>
            </div>
        </div>

        <h2 style="margin-top: 2rem;">Getting Started</h2>
        <ol style="margin: 1rem 0; padding-left: 2rem; color: var(--text-secondary); line-height: 1.8;">
            <li><strong>Create a circuit</strong> - Register your chess circuit</li>
            <li><strong>Register clubs</strong> - Clubs can join the circuit</li>
            <li><strong>Add players</strong> - Players register with clubs</li>
            <li><strong>Submit results</strong> - Record matches and confirm results</li>
            <li><strong>Monitor rankings</strong> - Track progress in real-time</li>
        </ol>

        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; text-align: center;">
            <p style="margin: 0 0 1rem 0; color: var(--text-secondary);">
                Ready to get started?
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="?page=create" class="btn btn-primary">Create Circuit</a>
                <a href="?page=contact" class="btn btn-secondary">Contact Us</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
