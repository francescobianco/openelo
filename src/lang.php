<?php
/**
 * OpenElo - Translations
 */

$translations = [
    'en' => [
        // General
        'site_title' => 'OpenELO',
        'site_tagline' => 'Easily run chess courses and school workshops',
        'site_description' => 'The simple platform for chess instructors: courses, school workshops, students and a smooth path into the Italian FSI circuit.',
        // LEGACY (rating-system mission) - kept for reference:
        // 'site_tagline' => 'Open Chess Rating System',
        // 'site_description' => 'A free, decentralized Elo rating system for chess communities outside FIDE circuits.',

        // Navigation
        'nav_home' => 'Home',
        'nav_circuits' => 'Circuits',
        'nav_clubs' => 'Clubs',
        'nav_rankings' => 'Rankings',
        'nav_submit_result' => 'Submit Result',
        'nav_create' => 'Create/Register',
        'nav_players' => 'Players',
        'nav_matches' => 'Matches',

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
        'feature_3_desc' => 'Clubs require approval from president and circuit manager. Players from player and president.',
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

        'result_white_wins' => 'White wins (1 - 0)',
        'result_black_wins' => 'Black wins (0 - 1)',
        'result_draw' => 'Draw (½ - ½)',

        // Circuit
        'circuit_create' => 'Create Circuit',
        'circuit_name' => 'Circuit Name',
        'circuit_owner_email' => 'Manager Email',
        'circuit_created' => 'Circuit created! Check your email to confirm.',
        'circuit_clubs' => 'Clubs',
        'circuit_players' => 'Players',
        'circuit_matches' => 'Matches',
        'no_circuits_associated' => 'No circuits associated',

        // Club
        'club_create' => 'Register Club',
        'club_name' => 'Club Name',
        'club_president_email' => 'President Email',
        'club_created' => 'Club registered! Both president and circuit manager will receive confirmation emails.',
        'club_join_circuit' => 'Join Another Circuit',
        'club_request_sent' => 'Request sent! Both president and circuit manager will receive emails.',
        'club_pending' => 'Pending',
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
        'rankings_position' => '',
        'rankings_player' => 'Player',
        'rankings_club' => 'Club',
        'rankings_rating' => 'Rating',
        'rankings_games' => 'Games',

        // Status
        'status_pending_president' => 'Waiting for president confirmation',
        'status_pending_circuit' => 'Waiting for circuit manager confirmation',
        'status_pending_player' => 'Waiting for player confirmation',
        'status_pending_both' => 'Waiting for both confirmations',
        'status_active' => 'Active',

        // Formulas
        'formula_classic_elo'            => 'Classic ELO List',
        'formula_ladder_no_draw'         => 'Ladder No Draw',
        'formula_knockout_no_draw'       => 'Knockout No Draw',
        'formula_ladder_3up_sliding'     => 'Ladder 3up Sliding',
        'formula_mobile_ranking'         => 'Mobile Ranking',

        // Formula descriptions (shown at the bottom of the rankings tab)
        'formula_desc_classic_elo'       => 'This ranking is based on ELO rating. Every match updates your score using the standard ELO formula with FIDE-style K factors (K=40 for new players, K=20 below 2200, K=10 above 2200). To climb it, defeat higher-rated opponents — the bigger the upset, the more points you earn.',
        'formula_desc_ladder_no_draw'    => 'This ranking is a ladder where draws are not allowed — every match must have a winner. To move up, defeat a higher-ranked opponent and take their position. There are no draws: every match must produce a clear winner.',
        'formula_desc_knockout_no_draw'  => 'This ranking works in elimination style, without draws. To advance, defeat your opponent — no draws are permitted and every match must produce a clear winner.',
        'formula_desc_ladder_3up_sliding'=> 'This ranking is a sliding ladder. To climb it, you may only challenge players within 3 positions above you. If you win, you take their exact position and all players in between slide down one spot. The ELO rating shown here is tracked for reference only — the official standing is determined by position.',
        'formula_desc_mobile_ranking'    => 'This ranking is position-based and driven by direct matches. Beat a higher-ranked opponent and you take their position — all players in between slide down one spot. Draws also count: drawing against a higher-ranked opponent moves you up one position. The ELO shown here is for analysis only; the official standing is determined by match results.',

        // Errors
        'error_required' => 'This field is required.',
        'error_email' => 'Invalid email address.',
        'error_not_found' => 'Not found.',
        'error_generic' => 'An error occurred. Please try again.',
        'error_same_player' => 'Players must be different.',
        'error_invalid_result' => 'Invalid result.',
        'error_email_exists' => 'Email already registered.',
        'error_already_member' => 'Already member of this circuit.',
        'error_draw_not_allowed' => 'Draws are not allowed in this circuit.',
        'error_ladder_gap' => 'You can only challenge players within 3 positions above you.',


        // ─────────────────────────────────────────────────────────────
        // Chess School Platform (instructors / classes / students)
        // ─────────────────────────────────────────────────────────────

        // Navigation (school area)
        'nav_dashboard' => 'Dashboard',
        'nav_classes' => 'Classes',
        'nav_students' => 'Students',
        'nav_account' => 'Account',
        'nav_login' => 'Log in',
        'nav_register' => 'Sign up',
        'nav_logout' => 'Log out',
        'nav_rating_area' => 'Rating & Circuits',

        // Landing page (school mission)
        'school_hero_title' => 'Easily run chess courses and school workshops',
        'school_hero_subtitle' => 'Classes, students and progress all in one place. Simple to use and ready for the Italian FSI circuit: youth games, provincial and school championships.',
        'school_hero_cta' => 'Start for free',
        'school_hero_secondary' => 'I already have an account',
        'school_feature_1_title' => 'Genuinely simple',
        'school_feature_1_desc' => 'Set up a course in thirty seconds and add your kids in a moment. Nothing to install, nothing to learn.',
        'school_feature_2_title' => 'Courses and school workshops',
        'school_feature_2_desc' => 'Run club courses and school workshops the same way, with parent and teacher contacts always at hand.',
        'school_feature_3_title' => 'Ready for the FSI circuit',
        'school_feature_3_desc' => 'Complete, tidy records: take your kids to youth games, provincial and school championships without rebuilding the lists every time.',
        'school_feature_4_title' => 'Your kids\' progress',
        'school_feature_4_desc' => 'Every student\'s level and rating, always up to date — from their first checkmate to their first tournament.',
        'school_features_title' => 'Everything an instructor needs',
        'school_cta_title' => 'Ready to start?',
        'school_how_it_works' => 'How it works',
        'school_step_1' => 'Create your account',
        'school_step_2' => 'Create your course or workshop',
        'school_step_3' => 'Add your kids',
        'school_step_4' => 'Follow their progress and take them to tournaments',

        // From the course to the tournament (FSI circuit)
        'fsi_title' => 'From the course to the tournament, friction free',
        'fsi_subtitle' => 'Your lists are already in the right shape for the FSI circuit.',
        'fsi_item_1' => 'Youth games',
        'fsi_item_2' => 'Provincial championships',
        'fsi_item_3' => 'School tournaments',
        'fsi_item_4' => 'Club tournaments',
        'fsi_note' => 'OpenELO does not replace the FSI: it keeps your data tidy, so entering a team takes minutes instead of evenings.',

        // Auth
        'auth_login_title' => 'Log in',
        'auth_login_submit' => 'Log in',
        'auth_register_title' => 'Create your instructor account',
        'auth_register_subtitle' => 'Free, no credit card. You will be teaching in two minutes.',
        'auth_register_submit' => 'Create account',
        'auth_email' => 'Email',
        'auth_password' => 'Password',
        'auth_password_confirm' => 'Confirm password',
        'auth_password_hint' => 'At least 8 characters.',
        'auth_first_name' => 'First name',
        'auth_last_name' => 'Last name',
        'auth_school_name' => 'School / academy name',
        'auth_city' => 'City',
        'auth_phone' => 'Phone',
        'auth_forgot_password' => 'Forgot your password?',
        'auth_forgot_title' => 'Reset your password',
        'auth_forgot_subtitle' => 'Enter your email and we will send you a reset link.',
        'auth_forgot_submit' => 'Send reset link',
        'auth_forgot_sent' => 'If that email belongs to an account, a reset link is on its way.',
        'auth_reset_title' => 'Choose a new password',
        'auth_reset_submit' => 'Save new password',
        'auth_reset_done' => 'Password updated. You can log in now.',
        'auth_reset_invalid' => 'This reset link is invalid or expired. Please request a new one.',
        'auth_no_account' => 'No account yet?',
        'auth_have_account' => 'Already registered?',
        'auth_logged_out' => 'You have been logged out.',
        'auth_welcome_back' => 'Welcome back',
        'auth_registered' => 'Account created! Check your inbox to confirm your email address.',
        'auth_verify_ok' => 'Email confirmed. Your account is fully active.',
        'auth_verify_failed' => 'This confirmation link is invalid or expired.',
        'auth_verify_banner' => 'Your email address is not confirmed yet.',
        'auth_verify_resend' => 'Resend confirmation email',
        'auth_verify_sent' => 'Confirmation email sent.',
        'auth_error_password_short' => 'Password must be at least 8 characters.',
        'auth_error_password_mismatch' => 'The two passwords do not match.',
        'auth_error_email_taken' => 'An account with this email already exists.',
        'auth_error_invalid_credentials' => 'Wrong email or password.',
        'auth_error_account_disabled' => 'This account is not active.',
        'auth_error_current_password' => 'Current password is not correct.',
        'auth_account_title' => 'Your account',
        'auth_profile' => 'Profile',
        'auth_profile_saved' => 'Profile updated.',
        'auth_change_password' => 'Change password',
        'auth_current_password' => 'Current password',
        'auth_new_password' => 'New password',
        'auth_password_changed' => 'Password updated.',

        // Dashboard
        'dash_title' => 'Dashboard',
        'dash_welcome' => 'Hi',
        'dash_active_classes' => 'Active classes',
        'dash_total_students' => 'Students',
        'dash_enrollments' => 'Enrollments',
        'dash_new_class' => 'New class',
        'dash_new_student' => 'New student',
        'dash_recent_classes' => 'Your classes',
        'dash_recent_students' => 'Latest students',
        'dash_empty_title' => 'Let\'s start with your first course',
        'dash_empty_desc' => 'Create a class, then add the kids who attend it. It takes a minute.',

        // Classes
        'class_list_title' => 'Classes',
        'class_new' => 'New class',
        'class_create' => 'Create class',
        'class_edit' => 'Edit class',
        'class_name' => 'Class name',
        'class_description' => 'Description',
        'class_level' => 'Level',
        'class_location' => 'Venue',
        'class_schedule' => 'Schedule',
        'class_start_date' => 'Start date',
        'class_end_date' => 'End date',
        'class_max_students' => 'Max students',
        'class_status' => 'Status',
        'class_created' => 'Class created.',
        'class_updated' => 'Class updated.',
        'class_archived' => 'Class archived.',
        'class_restored' => 'Class restored.',
        'class_deleted' => 'Class deleted.',
        'class_none' => 'No classes yet.',
        'class_students' => 'Students in this class',
        'class_add_student' => 'Add student',
        'class_enroll_existing' => 'Enroll an existing student',
        'class_enrolled' => 'Student enrolled.',
        'class_unenrolled' => 'Student removed from the class.',
        'class_already_enrolled' => 'This student is already enrolled in this class.',
        'class_no_students' => 'No students enrolled yet.',
        'class_archive' => 'Archive',
        'class_restore' => 'Restore',
        'class_tab_class' => 'Class',
        'class_tab_register' => 'Register',
        'class_tab_lessons' => 'Lessons',
        'class_tab_calendar' => 'Calendar',
        'class_tab_coming_soon' => 'This section will be available soon.',

        // Levels
        'level_beginner' => 'Beginner',
        'level_intermediate' => 'Intermediate',
        'level_advanced' => 'Advanced',
        'level_competitive' => 'Competitive',

        // Students
        'student_list_title' => 'Students',
        'student_new' => 'New student',
        'student_create' => 'Add student',
        'student_edit' => 'Edit student',
        'student_first_name' => 'First name',
        'student_last_name' => 'Last name',
        'student_full_name' => 'Last name First name',
        'student_birth_year' => 'Year',
        'student_email' => 'Email',
        'student_phone' => 'Phone',
        'student_birth_date' => 'Date of birth',
        'student_guardian' => 'Guardian (for minors)',
        'student_guardian_name' => 'Guardian name',
        'student_guardian_email' => 'Guardian email',
        'student_guardian_phone' => 'Guardian phone',
        'student_notes' => 'Notes',
        'student_rating' => 'Rating',
        'student_level' => 'Level',
        'student_created' => 'Student added.',
        'student_updated' => 'Student updated.',
        'student_deleted' => 'Student deleted.',
        'student_none' => 'No students yet.',
        'student_classes' => 'Classes attended',
        'student_no_classes' => 'Not enrolled in any class yet.',
        'student_search' => 'Search students...',
        'student_age' => 'Age',

        // Shared UI
        'common_save' => 'Save',
        'common_cancel' => 'Cancel',
        'common_delete' => 'Delete',
        'common_edit' => 'Edit',
        'common_back' => 'Back',
        'common_actions' => 'Actions',
        'common_optional' => 'optional',
        'common_active' => 'Active',
        'common_archived' => 'Archived',
        'common_confirm_delete' => 'Are you sure? This cannot be undone.',
        'common_not_set' => '—',

        // Footer
        'footer_text' => 'is open source software.',
        'footer_github' => 'View on GitHub',
    ],

    'it' => [
        // General
        'site_title' => 'OpenELO',
        'site_tagline' => 'Gestisci facilmente corsi di scacchi e laboratori scolastici',
        'site_description' => 'La piattaforma semplice per gli istruttori di scacchi: corsi, laboratori scolastici, studenti e un passaggio agevole verso il circuito FSI.',
        // LEGACY (missione sistema di rating) - conservata per riferimento:
        // 'site_tagline' => 'Sistema di Rating Scacchistico Aperto',
        // 'site_description' => 'Un sistema Elo gratuito e decentralizzato per comunità scacchistiche fuori dai circuiti FIDE.',

        // Navigation
        'nav_home' => 'Home',
        'nav_circuits' => 'Circuiti',
        'nav_clubs' => 'Circoli',
        'nav_rankings' => 'Classifiche',
        'nav_submit_result' => 'Inserisci Risultato',
        'nav_create' => 'Crea/Registra',
        'nav_players' => 'Giocatori',
        'nav_matches' => 'Partite',

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
        'feature_3_desc' => 'I circoli richiedono approvazione di presidente e responsabile circuito. I giocatori di giocatore e presidente.',
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

        'result_white_wins' => 'Vince il Bianco (1 - 0)',
        'result_black_wins' => 'Vince il Nero (0 - 1)',
        'result_draw' => 'Patta (½ - ½)',

        // Circuit
        'circuit_create' => 'Crea Circuito',
        'circuit_name' => 'Nome Circuito',
        'circuit_owner_email' => 'Email Responsabile',
        'circuit_created' => 'Circuito creato! Controlla la tua email per confermare.',
        'circuit_clubs' => 'Circoli',
        'circuit_players' => 'Giocatori',
        'circuit_matches' => 'Partite',
        'no_circuits_associated' => 'Nessun circuito associato',

        // Club
        'club_create' => 'Registra Circolo',
        'club_name' => 'Nome Circolo',
        'club_president_email' => 'Email Presidente',
        'club_created' => 'Circolo registrato! Sia il presidente che il responsabile del circuito riceveranno email di conferma.',
        'club_join_circuit' => 'Unisciti ad un Altro Circuito',
        'club_request_sent' => 'Richiesta inviata! Sia il presidente che il responsabile del circuito riceveranno email.',
        'club_pending' => 'In attesa',
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
        'rankings_title' => 'Classifica',
        'rankings_position' => '',
        'rankings_player' => 'Giocatore',
        'rankings_club' => 'Circolo',
        'rankings_rating' => 'Rating',
        'rankings_games' => 'Partite',

        // Status
        'status_pending_president' => 'In attesa conferma presidente',
        'status_pending_circuit' => 'In attesa conferma responsabile circuito',
        'status_pending_player' => 'In attesa conferma giocatore',
        'status_pending_both' => 'In attesa di entrambe le conferme',
        'status_active' => 'Attivo',

        // Formulas
        'formula_classic_elo'            => 'Lista ELO Classica',
        'formula_ladder_no_draw'         => 'Ladder No Draw',
        'formula_knockout_no_draw'       => 'Knockout No Draw',
        'formula_ladder_3up_sliding'     => 'Ladder 3up Scorrimento',
        'formula_mobile_ranking'         => 'Classifica Mobile',

        // Descrizioni formule (mostrate in fondo alla tab classifica)
        'formula_desc_classic_elo'       => 'Questa classifica è basata sul rating ELO. Ogni partita aggiorna il tuo punteggio con la formula ELO standard e fattori K stile FIDE (K=40 per nuovi giocatori, K=20 sotto 2200, K=10 sopra 2200). Per scalare questa classifica batti avversari con rating più alto — più è sorprendente il risultato, più punti guadagni.',
        'formula_desc_ladder_no_draw'    => 'Questa classifica è una scaletta dove i pareggi non sono ammessi — ogni partita deve avere un vincitore. Per salire, batti un avversario classificato più in alto e prendine il posto. Non esistono pareggi: ogni incontro deve produrre un vincitore.',
        'formula_desc_knockout_no_draw'  => 'Questa classifica funziona per eliminazione diretta, senza pareggi. Per avanzare, batti il tuo avversario — nessun pareggio è consentito e ogni partita deve produrre un vincitore netto.',
        'formula_desc_ladder_3up_sliding'=> 'Questa classifica è una scaletta scorrevole. Per scalare questa classifica puoi sfidare solo giocatori entro 3 posizioni sopra di te. Se vinci, prendi il loro esatto posto e tutti i giocatori intermedi scorrono di una posizione verso il basso. Il rating ELO mostrato qui viene tracciato solo come riferimento — la posizione ufficiale è determinata dagli scontri diretti.',
        'formula_desc_mobile_ranking'    => 'Questa classifica è basata sulla posizione ed è determinata dagli scontri diretti. Batti un avversario classificato più in alto e ne prendi il posto — tutti i giocatori intermedi scorrono di una posizione verso il basso. Anche i pareggi contano: pareggiare contro un avversario più in alto ti fa salire di una posizione. Il rating ELO mostrato qui è solo a fini di analisi; la posizione ufficiale è determinata dai risultati delle partite.',

        // Errors
        'error_required' => 'Campo obbligatorio.',
        'error_email' => 'Indirizzo email non valido.',
        'error_not_found' => 'Non trovato.',
        'error_generic' => 'Si è verificato un errore. Riprova.',
        'error_same_player' => 'I giocatori devono essere diversi.',
        'error_invalid_result' => 'Risultato non valido.',
        'error_email_exists' => 'Email già registrata.',
        'error_already_member' => 'Già membro di questo circuito.',
        'error_draw_not_allowed' => 'I pareggi non sono consentiti in questo circuito.',
        'error_ladder_gap' => 'Puoi sfidare solo giocatori entro 3 posizioni sopra di te.',


        // ─────────────────────────────────────────────────────────────
        // Piattaforma Scuola di Scacchi (istruttori / classi / studenti)
        // ─────────────────────────────────────────────────────────────

        // Navigazione (area scuola)
        'nav_dashboard' => 'Dashboard',
        'nav_classes' => 'Classi',
        'nav_students' => 'Studenti',
        'nav_account' => 'Account',
        'nav_login' => 'Accedi',
        'nav_register' => 'Registrati',
        'nav_logout' => 'Esci',
        'nav_rating_area' => 'Rating e Circuiti',

        // Landing page (missione scuola)
        'school_hero_title' => 'Gestisci facilmente corsi di scacchi e laboratori scolastici',
        'school_hero_subtitle' => 'Classi, studenti e progressi in un unico posto. Semplice da usare e già pronto per il circuito FSI: Giochi Giovanili, campionati provinciali e scolastici.',
        'school_hero_cta' => 'Inizia gratis',
        'school_hero_secondary' => 'Ho già un account',
        'school_feature_1_title' => 'Semplice, davvero',
        'school_feature_1_desc' => 'Crei un corso in trenta secondi e aggiungi i ragazzi in un attimo. Niente da installare, niente da imparare.',
        'school_feature_2_title' => 'Corsi e laboratori scolastici',
        'school_feature_2_desc' => 'Gestisci allo stesso modo i corsi del circolo e i laboratori nelle scuole, con i contatti di genitori e insegnanti sempre a portata di mano.',
        'school_feature_3_title' => 'Pronto per il circuito FSI',
        'school_feature_3_desc' => 'Anagrafiche complete e ordinate: porti i tuoi ragazzi ai Giochi Giovanili, ai campionati provinciali e scolastici senza rifare gli elenchi ogni volta.',
        'school_feature_4_title' => 'I progressi dei tuoi ragazzi',
        'school_feature_4_desc' => 'Livello e rating di ogni studente sempre aggiornati, dal primo scacco matto al primo torneo.',
        'school_features_title' => 'Tutto quello che serve a un istruttore',
        'school_cta_title' => 'Pronto a iniziare?',
        'school_how_it_works' => 'Come funziona',
        'school_step_1' => 'Crea il tuo account',
        'school_step_2' => 'Crea il corso o il laboratorio',
        'school_step_3' => 'Aggiungi i tuoi ragazzi',
        'school_step_4' => 'Segui i progressi e portali in gara',

        // Dal corso alla gara (circuito FSI)
        'fsi_title' => 'Dal corso alla gara, senza attriti',
        'fsi_subtitle' => 'I tuoi elenchi sono già nella forma giusta per il circuito FSI.',
        'fsi_item_1' => 'Giochi Giovanili',
        'fsi_item_2' => 'Campionati provinciali',
        'fsi_item_3' => 'Tornei scolastici',
        'fsi_item_4' => 'Tornei del circolo',
        'fsi_note' => 'OpenELO non sostituisce la FSI: tiene in ordine i tuoi dati, così iscrivere una squadra è questione di minuti invece che di serate.',

        // Autenticazione
        'auth_login_title' => 'Accedi',
        'auth_login_submit' => 'Accedi',
        'auth_register_title' => 'Crea il tuo account istruttore',
        'auth_register_subtitle' => 'Gratis, senza carta di credito. In due minuti sei operativo.',
        'auth_register_submit' => 'Crea account',
        'auth_email' => 'Email',
        'auth_password' => 'Password',
        'auth_password_confirm' => 'Conferma password',
        'auth_password_hint' => 'Almeno 8 caratteri.',
        'auth_first_name' => 'Nome',
        'auth_last_name' => 'Cognome',
        'auth_school_name' => 'Nome scuola / accademia',
        'auth_city' => 'Città',
        'auth_phone' => 'Telefono',
        'auth_forgot_password' => 'Password dimenticata?',
        'auth_forgot_title' => 'Reimposta la password',
        'auth_forgot_subtitle' => 'Inserisci la tua email e ti invieremo un link per reimpostarla.',
        'auth_forgot_submit' => 'Invia il link',
        'auth_forgot_sent' => 'Se questa email corrisponde a un account, il link è in arrivo.',
        'auth_reset_title' => 'Scegli una nuova password',
        'auth_reset_submit' => 'Salva la nuova password',
        'auth_reset_done' => 'Password aggiornata. Ora puoi accedere.',
        'auth_reset_invalid' => 'Questo link non è valido o è scaduto. Richiedine uno nuovo.',
        'auth_no_account' => 'Non hai ancora un account?',
        'auth_have_account' => 'Sei già registrato?',
        'auth_logged_out' => 'Sei uscito dal tuo account.',
        'auth_welcome_back' => 'Bentornato',
        'auth_registered' => 'Account creato! Controlla la posta per confermare il tuo indirizzo email.',
        'auth_verify_ok' => 'Email confermata. Il tuo account è pienamente attivo.',
        'auth_verify_failed' => 'Questo link di conferma non è valido o è scaduto.',
        'auth_verify_banner' => 'Il tuo indirizzo email non è ancora confermato.',
        'auth_verify_resend' => 'Invia di nuovo l\'email di conferma',
        'auth_verify_sent' => 'Email di conferma inviata.',
        'auth_error_password_short' => 'La password deve avere almeno 8 caratteri.',
        'auth_error_password_mismatch' => 'Le due password non coincidono.',
        'auth_error_email_taken' => 'Esiste già un account con questa email.',
        'auth_error_invalid_credentials' => 'Email o password errate.',
        'auth_error_account_disabled' => 'Questo account non è attivo.',
        'auth_error_current_password' => 'La password attuale non è corretta.',
        'auth_account_title' => 'Il tuo account',
        'auth_profile' => 'Profilo',
        'auth_profile_saved' => 'Profilo aggiornato.',
        'auth_change_password' => 'Cambia password',
        'auth_current_password' => 'Password attuale',
        'auth_new_password' => 'Nuova password',
        'auth_password_changed' => 'Password aggiornata.',

        // Dashboard
        'dash_title' => 'Dashboard',
        'dash_welcome' => 'Ciao',
        'dash_active_classes' => 'Classi attive',
        'dash_total_students' => 'Studenti',
        'dash_enrollments' => 'Iscrizioni',
        'dash_new_class' => 'Nuova classe',
        'dash_new_student' => 'Nuovo studente',
        'dash_recent_classes' => 'Le tue classi',
        'dash_recent_students' => 'Ultimi studenti',
        'dash_empty_title' => 'Iniziamo dal tuo primo corso',
        'dash_empty_desc' => 'Crea una classe, poi aggiungi i ragazzi che la frequentano. Ci vuole un minuto.',

        // Classi
        'class_list_title' => 'Classi',
        'class_new' => 'Nuova classe',
        'class_create' => 'Crea classe',
        'class_edit' => 'Modifica classe',
        'class_name' => 'Nome della classe',
        'class_description' => 'Descrizione',
        'class_level' => 'Livello',
        'class_location' => 'Sede',
        'class_schedule' => 'Orario',
        'class_start_date' => 'Data di inizio',
        'class_end_date' => 'Data di fine',
        'class_max_students' => 'Studenti max',
        'class_status' => 'Stato',
        'class_created' => 'Classe creata.',
        'class_updated' => 'Classe aggiornata.',
        'class_archived' => 'Classe archiviata.',
        'class_restored' => 'Classe ripristinata.',
        'class_deleted' => 'Classe eliminata.',
        'class_none' => 'Non hai ancora nessuna classe.',
        'class_students' => 'Studenti di questa classe',
        'class_add_student' => 'Aggiungi studente',
        'class_enroll_existing' => 'Iscrivi uno studente esistente',
        'class_enrolled' => 'Studente iscritto.',
        'class_unenrolled' => 'Studente rimosso dalla classe.',
        'class_already_enrolled' => 'Questo studente è già iscritto a questa classe.',
        'class_no_students' => 'Nessuno studente iscritto.',
        'class_archive' => 'Archivia',
        'class_restore' => 'Ripristina',
        'class_tab_class' => 'Classe',
        'class_tab_register' => 'Registro',
        'class_tab_lessons' => 'Lezioni',
        'class_tab_calendar' => 'Calendario',
        'class_tab_coming_soon' => 'Questa sezione sarà disponibile prossimamente.',

        // Livelli
        'level_beginner' => 'Principiante',
        'level_intermediate' => 'Intermedio',
        'level_advanced' => 'Avanzato',
        'level_competitive' => 'Agonistico',

        // Studenti
        'student_list_title' => 'Studenti',
        'student_new' => 'Nuovo studente',
        'student_create' => 'Aggiungi studente',
        'student_edit' => 'Modifica studente',
        'student_first_name' => 'Nome',
        'student_last_name' => 'Cognome',
        'student_full_name' => 'Cognome Nome',
        'student_birth_year' => 'Anno',
        'student_email' => 'Email',
        'student_phone' => 'Telefono',
        'student_birth_date' => 'Data di nascita',
        'student_guardian' => 'Genitore / tutore (per i minori)',
        'student_guardian_name' => 'Nome del genitore',
        'student_guardian_email' => 'Email del genitore',
        'student_guardian_phone' => 'Telefono del genitore',
        'student_notes' => 'Note',
        'student_rating' => 'Rating',
        'student_level' => 'Livello',
        'student_created' => 'Studente aggiunto.',
        'student_updated' => 'Studente aggiornato.',
        'student_deleted' => 'Studente eliminato.',
        'student_none' => 'Non hai ancora nessuno studente.',
        'student_classes' => 'Classi frequentate',
        'student_no_classes' => 'Non è iscritto a nessuna classe.',
        'student_search' => 'Cerca studenti...',
        'student_age' => 'Età',

        // UI condivisa
        'common_save' => 'Salva',
        'common_cancel' => 'Annulla',
        'common_delete' => 'Elimina',
        'common_edit' => 'Modifica',
        'common_back' => 'Indietro',
        'common_actions' => 'Azioni',
        'common_optional' => 'facoltativo',
        'common_active' => 'Attiva',
        'common_archived' => 'Archiviata',
        'common_confirm_delete' => 'Sei sicuro? L\'operazione non è reversibile.',
        'common_not_set' => '—',

        // Footer
        'footer_text' => 'è un software open source.',
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

    // 0. Forced language (see FORCE_LANG in config.php).
    //    We ship in Italian only for now: skip every detection step below so
    //    there is no ambiguity, but keep the multilanguage logic intact.
    if (defined('FORCE_LANG') && FORCE_LANG !== '' && in_array(FORCE_LANG, SUPPORTED_LANGS)) {
        $_currentLang = FORCE_LANG;
        return;
    }

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
