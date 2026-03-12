<?php
/**
 * OpenElo - Security & Privacy Page
 */
?>

<div class="container">
    <?php if ($lang === 'it'): ?>
    <!-- Versione Italiana -->
    <div class="page-header">
        <h1>♜ Sicurezza e Privacy</h1>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto;">

        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.7;">
            La privacy dei nostri utenti è una priorità fondamentale. Questa pagina descrive le nostre scelte architetturali
            a tutela dei dati personali delle persone registrate sulla piattaforma.
        </p>

        <h2 style="margin-top: 2rem;">Indirizzi Email</h2>
        <p style="color: var(--text-secondary);">
            Nessun indirizzo email viene mai esposto pubblicamente dalla piattaforma, né nelle pagine visibili agli utenti
            né tramite API. Gli indirizzi email vengono raccolti esclusivamente per un unico scopo tecnico:
        </p>
        <div style="background: var(--bg-secondary); border-left: 4px solid var(--accent); padding: 1rem 1.5rem; border-radius: 0 8px 8px 0; margin: 1rem 0;">
            <strong>Le email vengono utilizzate unicamente per inviare i link di approvazione</strong> delle operazioni
            che richiedono conferma da parte dei responsabili dei circuiti e dei circoli (es. iscrizione di un club,
            registrazione di un giocatore, conferma di un risultato).
        </div>
        <p style="color: var(--text-secondary);">
            Gli indirizzi email non vengono usati per comunicazioni promozionali, notifiche automatiche periodiche,
            condivisione con terze parti o qualsiasi altra attività interna o esterna alla piattaforma.
            Una volta esaurita la funzione di approvazione, l'indirizzo rimane nel database esclusivamente
            per consentire future operazioni di gestione da parte del titolare.
        </p>

        <h2 style="margin-top: 2rem;">Modalità Protetta per i Club</h2>
        <p style="color: var(--text-secondary);">
            Per tutelare la privacy dei giocatori — in particolare dei minori o di chi preferisce che nome e cognome
            non siano visibili pubblicamente — ogni club può attivare la <strong>modalità protetta</strong>.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin: 1.5rem 0;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">Modalità Normale</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    I nomi dei giocatori sono visibili a tutti i visitatori della pagina del club, come di consueto
                    per le classifiche pubbliche.
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--accent);">
                <h3 style="margin-top: 0; color: var(--accent);">Modalità Protetta</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    I nomi dei giocatori sono oscurati per tutti gli utenti anonimi. Solo i giocatori del club,
                    autenticati tramite il proprio link personale, possono vedere i nomi in chiaro.
                </p>
            </div>
        </div>

        <h3 style="margin-top: 1.5rem;">Come funziona tecnicamente</h3>
        <p style="color: var(--text-secondary);">
            Quando un club attiva la modalità protetta, i nomi dei giocatori vengono cifrati lato server prima di essere
            memorizzati. La chiave di decifratura non è mai esposta nelle pagine pubbliche. Il server la applica
            esclusivamente quando la richiesta proviene da un giocatore del club autenticato tramite il proprio token
            personale sicuro. Questo garantisce che anche in caso di accesso non autorizzato al database,
            i nomi rimangano illeggibili senza la chiave corretta.
        </p>
        <p style="color: var(--text-secondary);">
            Nella pagina di ogni club è presente un'indicazione visibile dello stato della modalità protetta,
            così che i responsabili e i giocatori sappiano sempre quale livello di visibilità è attivo.
        </p>

        <h2 style="margin-top: 2rem;">Riepilogo dei Principi</h2>
        <ul style="color: var(--text-secondary); line-height: 2; padding-left: 1.5rem;">
            <li>Nessuna email o contatto personale è mai esposto pubblicamente</li>
            <li>Le email servono esclusivamente per i flussi di approvazione interni</li>
            <li>I club possono scegliere di proteggere i nomi dei propri giocatori</li>
            <li>La cifratura dei nomi avviene lato server, con chiavi mai accessibili al pubblico</li>
            <li>La piattaforma è open source: chiunque può verificare il codice su GitHub</li>
        </ul>

        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; text-align: center;">
            <p style="margin: 0 0 1rem 0; color: var(--text-secondary);">
                Hai domande sulla gestione dei dati o vuoi segnalare un problema di sicurezza?
            </p>
            <a href="?page=contact" class="btn btn-secondary">Contattaci</a>
        </div>
    </div>

    <?php else: ?>
    <!-- English Version -->
    <div class="page-header">
        <h1>♜ Security & Privacy</h1>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto;">

        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.7;">
            The privacy of our users is a core priority. This page describes our architectural choices
            to protect the personal data of people registered on the platform.
        </p>

        <h2 style="margin-top: 2rem;">Email Addresses</h2>
        <p style="color: var(--text-secondary);">
            No email address is ever publicly exposed by the platform — neither on pages visible to users
            nor through the API. Email addresses are collected exclusively for a single technical purpose:
        </p>
        <div style="background: var(--bg-secondary); border-left: 4px solid var(--accent); padding: 1rem 1.5rem; border-radius: 0 8px 8px 0; margin: 1rem 0;">
            <strong>Emails are used solely to send approval links</strong> for operations that require
            confirmation from circuit or club administrators (e.g. club registration, player enrollment,
            match result confirmation).
        </div>
        <p style="color: var(--text-secondary);">
            Email addresses are not used for promotional communications, periodic automated notifications,
            sharing with third parties, or any other internal or external activity on the platform.
            Once the approval step is complete, the address remains in the database solely to allow
            future management operations by its owner.
        </p>

        <h2 style="margin-top: 2rem;">Protected Mode for Clubs</h2>
        <p style="color: var(--text-secondary);">
            To protect player privacy — especially for minors or anyone who prefers that their full name
            not be publicly visible — each club can enable <strong>protected mode</strong>.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin: 1.5rem 0;">
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px;">
                <h3 style="margin-top: 0; color: var(--accent);">Normal Mode</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Player names are visible to all visitors of the club page, as is standard
                    for public leaderboards.
                </p>
            </div>
            <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--accent);">
                <h3 style="margin-top: 0; color: var(--accent);">Protected Mode</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Player names are hidden from all anonymous visitors. Only players belonging to the club,
                    authenticated via their personal link, can see names in plain text.
                </p>
            </div>
        </div>

        <h3 style="margin-top: 1.5rem;">How It Works Technically</h3>
        <p style="color: var(--text-secondary);">
            When a club enables protected mode, player names are encrypted server-side before being stored.
            The decryption key is never exposed on public pages. The server applies it only when the request
            comes from a club member authenticated via their secure personal token. This ensures that even
            in the event of unauthorized database access, names remain unreadable without the correct key.
        </p>
        <p style="color: var(--text-secondary);">
            Each club page displays a clear indicator of whether protected mode is active, so administrators
            and players always know the current visibility level.
        </p>

        <h2 style="margin-top: 2rem;">Summary of Principles</h2>
        <ul style="color: var(--text-secondary); line-height: 2; padding-left: 1.5rem;">
            <li>No email or personal contact is ever publicly exposed</li>
            <li>Emails are used exclusively for internal approval workflows</li>
            <li>Clubs can choose to protect their players' names</li>
            <li>Name encryption happens server-side, with keys never accessible to the public</li>
            <li>The platform is open source: anyone can verify the code on GitHub</li>
        </ul>

        <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; text-align: center;">
            <p style="margin: 0 0 1rem 0; color: var(--text-secondary);">
                Have questions about data handling or want to report a security issue?
            </p>
            <a href="?page=contact" class="btn btn-secondary">Contact Us</a>
        </div>
    </div>
    <?php endif; ?>
</div>
