<?php
/**
 * OpenElo - Translations
 */

$translations = [
    'en' => [
        // General
        'site_title' => 'OpenELO',
        'site_tagline' => 'Open Chess Rating System',
        'site_description' => 'A free, decentralized Elo rating system for chess communities outside FIDE circuits.',

        // Navigation
        'nav_home' => 'Home',
        'nav_circuits' => 'Circuits',
        'nav_clubs' => 'Clubs',
        'nav_rankings' => 'Rankings',
        'nav_submit_result' => 'Submit Result',
        'nav_create' => 'Create',

        // Landing page
        'hero_title' => 'Your Rating, Your Circuit',
        'hero_subtitle' => 'A decentralized Elo rating system built on trust, not bureaucracy.',
        'hero_cta' => 'Explore Circuits',
        'hero_create' => 'Create Circuit',

        'feature_1_title' => 'Local Trust',
        'feature_1_desc' => 'Ratings are valid within trust circuits. No central authority, just community validation.',
        'feature_2_title' => 'Email-Based',
        'feature_2_desc' => 'No passwords, no logins. Everything works through email confirmations.',
        'feature_3_title' => 'Double Validation',
        'feature_3_desc' => 'Clubs require approval from president and circuit owner. Players from player and president.',
        'feature_4_title' => 'FIDE-style K Factor',
        'feature_4_desc' => 'K=40 for new players, K=20 under 2200, K=10 above. Familiar and fair.',

        'how_it_works' => 'How It Works',
        'step_1' => 'Create a circuit',
        'step_2' => 'Register your club in a circuit',
        'step_3' => 'Add players to your club',
        'step_4' => 'Submit and validate matches',
        'step_5' => 'Watch ratings evolve',

        // Forms
        'form_name' => 'Name',
        'form_email' => 'Email',
        'form_first_name' => 'First Name',
        'form_last_name' => 'Last Name',
        'form_circuit' => 'Circuit',
        'form_club' => 'Club',
        'form_player' => 'Player',
        'form_white' => 'White',
        'form_black' => 'Black',
        'form_result' => 'Result',
        'form_submit' => 'Submit',
        'form_cancel' => 'Cancel',
        'form_select' => 'Select...',

        'result_white_wins' => 'White wins (1-0)',
        'result_black_wins' => 'Black wins (0-1)',
        'result_draw' => 'Draw (½-½)',

        // Circuit
        'circuit_create' => 'Create Circuit',
        'circuit_name' => 'Circuit Name',
        'circuit_owner_email' => 'Owner Email',
        'circuit_created' => 'Circuit created! Check your email to confirm.',
        'circuit_clubs' => 'Clubs',
        'circuit_players' => 'Players',
        'circuit_matches' => 'Matches',

        // Club
        'club_create' => 'Create Club',
        'club_name' => 'Club Name',
        'club_president_email' => 'President Email',
        'club_created' => 'Club created! Both president and circuit owner will receive confirmation emails.',
        'club_join_circuit' => 'Join Another Circuit',
        'club_request_sent' => 'Request sent! Both president and circuit owner will receive emails.',
        'club_pending' => 'Pending approval',
        'club_active' => 'Active',
        'club_view' => 'View Club',

        // Player
        'player_register' => 'Register Player',
        'player_registered' => 'Player registered! Both player and club president will receive confirmation emails.',
        'player_rating' => 'Rating',
        'player_games' => 'Games',
        'player_view' => 'View Profile',
        'player_change_club' => 'Change Club',
        'player_transfer_requested' => 'Transfer requested! Both player and new club president will receive emails.',

        // Match
        'match_submit' => 'Submit Match Result',
        'match_submitted' => 'Match submitted! Both players and the club president will receive confirmation emails.',
        'match_pending' => 'Pending confirmations',
        'match_confirmed' => 'Confirmed',

        // Confirmation
        'confirm_success' => 'Confirmed successfully!',
        'confirm_error' => 'Invalid or expired token.',
        'confirm_already' => 'Already confirmed.',
        'confirm_waiting' => 'Waiting for other confirmations',

        // Rankings
        'rankings_title' => 'Rankings',
        'rankings_position' => '#',
        'rankings_player' => 'Player',
        'rankings_club' => 'Club',
        'rankings_rating' => 'Rating',
        'rankings_games' => 'Games',

        // Status
        'status_pending_president' => 'Waiting for president confirmation',
        'status_pending_circuit' => 'Waiting for circuit owner confirmation',
        'status_pending_player' => 'Waiting for player confirmation',
        'status_pending_both' => 'Waiting for both confirmations',
        'status_active' => 'Active',

        // Errors
        'error_required' => 'This field is required.',
        'error_email' => 'Invalid email address.',
        'error_not_found' => 'Not found.',
        'error_generic' => 'An error occurred. Please try again.',
        'error_same_player' => 'Players must be different.',
        'error_invalid_result' => 'Invalid result.',
        'error_email_exists' => 'Email already registered.',
        'error_already_member' => 'Already member of this circuit.',

        // Footer
        'footer_text' => 'OpenELO is open source software.',
        'footer_github' => 'View on GitHub',
    ],

    'it' => [
        // General
        'site_title' => 'OpenELO',
        'site_tagline' => 'Sistema di Rating Scacchistico Aperto',
        'site_description' => 'Un sistema Elo gratuito e decentralizzato per comunità scacchistiche fuori dai circuiti FIDE.',

        // Navigation
        'nav_home' => 'Home',
        'nav_circuits' => 'Circuiti',
        'nav_clubs' => 'Circoli',
        'nav_rankings' => 'Classifiche',
        'nav_submit_result' => 'Inserisci Risultato',
        'nav_create' => 'Crea',

        // Landing page
        'hero_title' => 'Il Tuo Rating, Il Tuo Circuito',
        'hero_subtitle' => 'Un sistema ELO decentralizzato basato sulla fiducia, non sulla burocrazia.',
        'hero_cta' => 'Esplora Circuiti',
        'hero_create' => 'Crea Circuito',

        'feature_1_title' => 'Fiducia Locale',
        'feature_1_desc' => 'I rating sono validi nei circuiti di fiducia. Nessuna autorità centrale, solo validazione della comunità.',
        'feature_2_title' => 'Basato su Email',
        'feature_2_desc' => 'Niente password, niente login. Tutto funziona tramite conferme via email.',
        'feature_3_title' => 'Doppia Validazione',
        'feature_3_desc' => 'I circoli richiedono approvazione di presidente e proprietario circuito. I giocatori di giocatore e presidente.',
        'feature_4_title' => 'Fattore K stile FIDE',
        'feature_4_desc' => 'K=40 per nuovi giocatori, K=20 sotto 2200, K=10 sopra. Familiare e giusto.',

        'how_it_works' => 'Come Funziona',
        'step_1' => 'Crea un circuito',
        'step_2' => 'Registra il tuo circolo in un circuito',
        'step_3' => 'Aggiungi giocatori al circolo',
        'step_4' => 'Inserisci e valida le partite',
        'step_5' => 'Guarda evolvere i rating',

        // Forms
        'form_name' => 'Nome',
        'form_email' => 'Email',
        'form_first_name' => 'Nome',
        'form_last_name' => 'Cognome',
        'form_circuit' => 'Circuito',
        'form_club' => 'Circolo',
        'form_player' => 'Giocatore',
        'form_white' => 'Bianco',
        'form_black' => 'Nero',
        'form_result' => 'Risultato',
        'form_submit' => 'Invia',
        'form_cancel' => 'Annulla',
        'form_select' => 'Seleziona...',

        'result_white_wins' => 'Vince il Bianco (1-0)',
        'result_black_wins' => 'Vince il Nero (0-1)',
        'result_draw' => 'Patta (½-½)',

        // Circuit
        'circuit_create' => 'Crea Circuito',
        'circuit_name' => 'Nome Circuito',
        'circuit_owner_email' => 'Email Proprietario',
        'circuit_created' => 'Circuito creato! Controlla la tua email per confermare.',
        'circuit_clubs' => 'Circoli',
        'circuit_players' => 'Giocatori',
        'circuit_matches' => 'Partite',

        // Club
        'club_create' => 'Crea Circolo',
        'club_name' => 'Nome Circolo',
        'club_president_email' => 'Email Presidente',
        'club_created' => 'Circolo creato! Sia il presidente che il proprietario del circuito riceveranno email di conferma.',
        'club_join_circuit' => 'Unisciti ad un Altro Circuito',
        'club_request_sent' => 'Richiesta inviata! Sia il presidente che il proprietario del circuito riceveranno email.',
        'club_pending' => 'In attesa di approvazione',
        'club_active' => 'Attivo',
        'club_view' => 'Vedi Circolo',

        // Player
        'player_register' => 'Registra Giocatore',
        'player_registered' => 'Giocatore registrato! Sia il giocatore che il presidente del circolo riceveranno email di conferma.',
        'player_rating' => 'Rating',
        'player_games' => 'Partite',
        'player_view' => 'Vedi Profilo',
        'player_change_club' => 'Cambia Circolo',
        'player_transfer_requested' => 'Trasferimento richiesto! Sia il giocatore che il presidente del nuovo circolo riceveranno email.',

        // Match
        'match_submit' => 'Inserisci Risultato Partita',
        'match_submitted' => 'Partita inserita! Entrambi i giocatori e il presidente del circolo riceveranno email di conferma.',
        'match_pending' => 'Conferme in attesa',
        'match_confirmed' => 'Confermata',

        // Confirmation
        'confirm_success' => 'Confermato con successo!',
        'confirm_error' => 'Token non valido o scaduto.',
        'confirm_already' => 'Già confermato.',
        'confirm_waiting' => 'In attesa di altre conferme',

        // Rankings
        'rankings_title' => 'Classifiche',
        'rankings_position' => '#',
        'rankings_player' => 'Giocatore',
        'rankings_club' => 'Circolo',
        'rankings_rating' => 'Rating',
        'rankings_games' => 'Partite',

        // Status
        'status_pending_president' => 'In attesa conferma presidente',
        'status_pending_circuit' => 'In attesa conferma proprietario circuito',
        'status_pending_player' => 'In attesa conferma giocatore',
        'status_pending_both' => 'In attesa di entrambe le conferme',
        'status_active' => 'Attivo',

        // Errors
        'error_required' => 'Campo obbligatorio.',
        'error_email' => 'Indirizzo email non valido.',
        'error_not_found' => 'Non trovato.',
        'error_generic' => 'Si è verificato un errore. Riprova.',
        'error_same_player' => 'I giocatori devono essere diversi.',
        'error_invalid_result' => 'Risultato non valido.',
        'error_email_exists' => 'Email già registrata.',
        'error_already_member' => 'Già membro di questo circuito.',

        // Footer
        'footer_text' => 'OpenELO è software open source.',
        'footer_github' => 'Vedi su GitHub',
    ],
];

// Store detected language to avoid multiple cookie attempts
$_currentLang = null;

/**
 * Initialize language (call before any output)
 */
function initLang(): void {
    global $_currentLang;

    // 1. Check URL parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
        $_currentLang = $_GET['lang'];
        setcookie('openelo_lang', $_currentLang, time() + 86400 * 365, '/');
        return;
    }

    // 2. Check cookie
    if (isset($_COOKIE['openelo_lang']) && in_array($_COOKIE['openelo_lang'], SUPPORTED_LANGS)) {
        $_currentLang = $_COOKIE['openelo_lang'];
        return;
    }

    // 3. Auto-detect from browser
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, SUPPORTED_LANGS)) {
            $_currentLang = $browserLang;
            return;
        }
    }

    $_currentLang = DEFAULT_LANG;
}

/**
 * Get current language
 */
function getCurrentLang(): string {
    global $_currentLang;

    if ($_currentLang === null) {
        initLang();
    }

    return $_currentLang;
}

/**
 * Translate key
 */
function __($key): string {
    global $translations;
    $lang = getCurrentLang();
    return $translations[$lang][$key] ?? $translations[DEFAULT_LANG][$key] ?? $key;
}

/**
 * Get all translations for current language
 */
function getTranslations(): array {
    global $translations;
    return $translations[getCurrentLang()];
}
