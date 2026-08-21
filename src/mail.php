<?php
/**
 * OpenElo - Email Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

// Palette delle email: gli stessi colori del sito (vedi :root in public/style.css)
define('MAIL_BUTTON_COLOR',  '#7FB236'); // verde: azione principale
define('MAIL_BUTTON_DANGER', '#E05138'); // rosso: cancellazioni, reset password
define('MAIL_BUTTON_INFO',   '#2E9BE6'); // blu: comunicazioni

/**
 * Get email subject prefix: always "OpenELO - ", with env tag for non-production
 */
function getEmailSubjectPrefix(): string {
    if (APP_ENV === '') return 'OpenELO - ';
    return 'OpenELO [' . strtoupper(APP_ENV) . '] - ';
}

/**
 * Darken a hex colour — used for the "step" under the buttons, the same
 * toy-like look the site has.
 */
function darkenColor(string $hex, float $amount = 0.8): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $rgb = array_map(function(string $part) use ($amount): int {
        return max(0, min(255, (int)round(hexdec($part) * $amount)));
    }, str_split($hex, 2));

    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

/**
 * Send confirmation email
 *
 * Stesso look del sito: fondo chiaro, card bianca arrotondata, bottone pieno
 * con lo scalino sotto. È costruita con tabelle e stili inline perché i client
 * di posta buttano via quasi tutto quello che un browser capisce; il blocco
 * <style> serve solo ai pochi client che lo rispettano.
 */
function sendConfirmationEmail(string $to, string $subject, string $message, string $confirmUrl, string $buttonColor = MAIL_BUTTON_COLOR, ?string $buttonLabel = null): bool {
    $subject = getEmailSubjectPrefix() . $subject;
    $lang = getCurrentLang();

    $buttonShadow = darkenColor($buttonColor, 0.78);
    $confirmLabel = $buttonLabel ?: ($lang === 'it' ? 'Conferma' : 'Confirm');
    $copyLabel    = $lang === 'it' ? 'Oppure copia e incolla questo link nel browser:' : 'Or copy and paste this link into your browser:';
    $tagline      = __('site_tagline');
    $logoUrl      = 'https://raw.githubusercontent.com/francescobianco/openelo/main/public/logo.png';
    $font         = "'Nunito','Trebuchet MS',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif";

    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{$subject}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap">
    <style>
        body { margin: 0; padding: 0; background: #F1F6FA; }
        @media only screen and (max-width: 620px) {
            .pad-side { padding-left: 22px !important; padding-right: 22px !important; }
            .btn-link { padding: 15px 28px !important; font-size: 16px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#F1F6FA; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F6FA;">
        <tr>
            <td align="center" style="padding:28px 14px 34px;">

                <!-- Card -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background:#FFFFFF; border-radius:20px; border:1px solid #DBE6EF;">

                    <!-- Testata con il logo -->
                    <tr>
                        <td align="center" style="padding:30px 24px 22px;">
                            <img src="{$logoUrl}" alt="OpenELO" width="46" height="46" style="display:inline-block; vertical-align:middle; border:0; border-radius:12px;">
                            <span style="display:inline-block; vertical-align:middle; margin-left:10px; font-family:{$font}; font-size:26px; font-weight:900; letter-spacing:-0.5px; color:#21374D;">Open<span style="color:#FF9E1B;">ELO</span></span>
                        </td>
                    </tr>

                    <!-- Filo colorato di brand -->
                    <tr>
                        <td style="font-size:0; line-height:0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="34%" height="6" style="background:#7FB236; font-size:0; line-height:0;">&nbsp;</td>
                                    <td width="33%" height="6" style="background:#FF9E1B; font-size:0; line-height:0;">&nbsp;</td>
                                    <td width="33%" height="6" style="background:#2E9BE6; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Messaggio -->
                    <tr>
                        <td class="pad-side" style="padding:34px 40px 4px; font-family:{$font}; font-size:17px; line-height:1.7; color:#21374D; font-weight:400;">
                            {$message}
                        </td>
                    </tr>

                    <!-- Bottone -->
                    <tr>
                        <td align="center" style="padding:26px 40px 30px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="background:{$buttonColor}; border-radius:14px; border-bottom:4px solid {$buttonShadow};">
                                        <a class="btn-link" href="{$confirmUrl}" style="display:inline-block; padding:16px 42px; font-family:{$font}; font-size:17px; font-weight:800; color:#FFFFFF; text-decoration:none;">{$confirmLabel}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Link di ripiego, per chi non può cliccare il bottone -->
                    <tr>
                        <td class="pad-side" style="padding:0 40px 34px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F9FC; border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px; font-family:{$font}; font-size:13px; line-height:1.6; color:#647F97; font-weight:600;">
                                        {$copyLabel}<br>
                                        <span style="font-size:12px; color:#1B7CC0; word-break:break-all;">{$confirmUrl}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Piè di pagina -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%;">
                    <tr>
                        <td align="center" style="padding:22px 20px 0; font-family:{$font}; font-size:13px; line-height:1.6; color:#8298AC; font-weight:600;">
                            <strong style="color:#647F97;">OpenELO</strong> &middot; {$tagline}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
    ];

    // For development, log emails instead of sending
    if (SMTP_HOST === 'smtp.example.com') {
        $logDir = dirname(__DIR__) . '/data/emails';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/' . date('Y-m-d_H-i-s') . '_' . md5($to . microtime()) . '.html';
        file_put_contents($logFile, "To: {$to}\nSubject: {$subject}\n\n{$htmlMessage}");
        return true;
    }

    return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}

/**
 * Send circuit creation confirmation
 */
function sendCircuitConfirmation(string $email, string $circuitName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma creazione circuito: {$circuitName}";
        $message = "Hai richiesto di creare il circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare e attivare il circuito.";
    } else {
        $subject = "Confirm circuit creation: {$circuitName}";
        $message = "You requested to create the circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to confirm and activate the circuit.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send club creation confirmation to president
 */
function sendClubPresidentConfirmation(string $email, string $clubName, string $circuitName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma creazione circolo: {$clubName}";
        $message = "Hai richiesto di creare il circolo <strong>{$clubName}</strong> nel circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare. Il responsabile del circuito dovrà anche approvare l'adesione.";
    } else {
        $subject = "Confirm club creation: {$clubName}";
        $message = "You requested to create the club <strong>{$clubName}</strong> in circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to confirm. The circuit manager will also need to approve the membership.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send club join request to circuit manager
 */
function sendClubCircuitConfirmation(string $ownerEmail, string $clubName, string $circuitName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Richiesta adesione circolo: {$clubName}";
        $message = "Il circolo <strong>{$clubName}</strong> ha richiesto di aderire al circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare l'adesione.";
    } else {
        $subject = "Club membership request: {$clubName}";
        $message = "The club <strong>{$clubName}</strong> has requested to join the circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to approve the membership.";
    }

    return sendConfirmationEmail($ownerEmail, $subject, $message, $url);
}

/**
 * Send player registration confirmation to player
 */
function sendPlayerSelfConfirmation(string $email, string $playerName, string $clubName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma registrazione giocatore";
        $message = "È stata richiesta la tua registrazione come giocatore <strong>{$playerName}</strong> nel circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare. Il presidente del circolo dovrà anche approvare.";
    } else {
        $subject = "Confirm player registration";
        $message = "Registration has been requested for you as player <strong>{$playerName}</strong> in club <strong>{$clubName}</strong>.<br><br>Click the button below to confirm. The club president will also need to approve.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send player registration confirmation to president
 */
function sendPlayerPresidentConfirmation(string $presidentEmail, string $playerName, string $clubName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Approvazione nuovo giocatore: {$playerName}";
        $message = "Il giocatore <strong>{$playerName}</strong> ha richiesto di unirsi al circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare l'iscrizione.";
    } else {
        $subject = "Approve new player: {$playerName}";
        $message = "Player <strong>{$playerName}</strong> has requested to join club <strong>{$clubName}</strong>.<br><br>Click the button below to approve the registration.";
    }

    return sendConfirmationEmail($presidentEmail, $subject, $message, $url);
}

/**
 * Send match confirmation request
 */
function sendMatchConfirmation(string $email, string $role, array $matchDetails, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    $white = $matchDetails['white_name'];
    $black = $matchDetails['black_name'];
    $result = $matchDetails['result'];
    $circuit = $matchDetails['circuit_name'];

    if ($lang === 'it') {
        $roleText = [
            'president' => 'presidente del circolo',
            'circuit_manager' => 'responsabile del circuito',
            'player' => 'giocatore'
        ][$role] ?? 'giocatore';
        $subject = "Conferma partita: {$white} vs {$black}";
        $message = "Come <strong>{$roleText}</strong>, ti viene chiesto di confermare il seguente risultato:<br><br>
            <strong>Circuito:</strong> {$circuit}<br>
            <strong>♔ Bianco:</strong> {$white}<br>
            <strong>♚ Nero:</strong> {$black}<br>
            <strong>Risultato:</strong> {$result}<br><br>
            Clicca il pulsante qui sotto per confermare.";
    } else {
        $roleText = [
            'president' => 'club president',
            'circuit_manager' => 'circuit manager',
            'player' => 'player'
        ][$role] ?? 'player';
        $subject = "Confirm match: {$white} vs {$black}";
        $message = "As <strong>{$roleText}</strong>, you are asked to confirm the following result:<br><br>
            <strong>Circuit:</strong> {$circuit}<br>
            <strong>♔ White:</strong> {$white}<br>
            <strong>♚ Black:</strong> {$black}<br>
            <strong>Result:</strong> {$result}<br><br>
            Click the button below to confirm.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send club transfer confirmation to player
 */
function sendTransferPlayerConfirmation(string $email, string $playerName, string $newClubName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "♟→♖ Conferma trasferimento circolo";
        $message = "♟ Hai richiesto di trasferirti al circolo <strong>{$newClubName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare. Il presidente del nuovo circolo dovrà anche approvare.";
    } else {
        $subject = "♟→♖ Confirm club transfer";
        $message = "♟ You requested to transfer to club <strong>{$newClubName}</strong>.<br><br>Click the button below to confirm. The new club president will also need to approve.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send club transfer confirmation to new president
 */
function sendTransferPresidentConfirmation(string $presidentEmail, string $playerName, string $clubName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "♟→♖ Richiesta trasferimento: {$playerName}";
        $message = "♟ Il giocatore <strong>{$playerName}</strong> ha richiesto di trasferirsi al circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare il trasferimento.";
    } else {
        $subject = "♟→♖ Transfer request: {$playerName}";
        $message = "♟ Player <strong>{$playerName}</strong> has requested to transfer to club <strong>{$clubName}</strong>.<br><br>Click the button below to approve the transfer.";
    }

    return sendConfirmationEmail($presidentEmail, $subject, $message, $url);
}

/**
 * Send deletion request notification
 */
function sendDeletionRequest(string $to, string $entityType, string $entityName, string $requesterEmail, string $reason, int $requestId, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=deletion&id=' . $requestId . '&token=' . $token;

    $entityTypeText = [
        'circuit' => $lang === 'it' ? 'circuito' : 'circuit',
        'club' => $lang === 'it' ? 'circolo' : 'club',
        'player' => $lang === 'it' ? 'giocatore' : 'player',
        'match' => $lang === 'it' ? 'partita' : 'match',
    ];

    if ($lang === 'it') {
        $subject = "Richiesta eliminazione: {$entityName}";
        $message = "È stata ricevuta una richiesta di eliminazione per il {$entityTypeText[$entityType]} <strong>{$entityName}</strong>.<br><br>
            <strong>Richiedente:</strong> {$requesterEmail}<br>
            <strong>Motivo:</strong> {$reason}<br><br>
            Clicca il pulsante qui sotto per approvare o rifiutare la richiesta.";
    } else {
        $subject = "Deletion request: {$entityName}";
        $message = "A deletion request has been received for the {$entityTypeText[$entityType]} <strong>{$entityName}</strong>.<br><br>
            <strong>Requester:</strong> {$requesterEmail}<br>
            <strong>Reason:</strong> {$reason}<br><br>
            Click the button below to approve or reject the request.";
    }

    return sendConfirmationEmail($to, $subject, $message, $url, MAIL_BUTTON_DANGER);
}

/**
 * Send protected mode toggle confirmation to president
 */
function sendProtectedModeConfirmation(string $presidentEmail, string $clubName, string $action, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $actionText = $action === 'enable' ? 'attivare' : 'disattivare';
        $subject = "&#128274; Richiesta modalità protetta: {$clubName}";
        $message = "È stata richiesta la <strong>{$actionText} la modalità protetta</strong> per il circolo <strong>{$clubName}</strong>.<br><br>
            Quando attiva, i nomi dei giocatori del circolo saranno visibili solo ai membri autenticati tramite il loro link personale.<br><br>
            Clicca il pulsante qui sotto per confermare questa modifica.";
    } else {
        $actionText = $action === 'enable' ? 'enable' : 'disable';
        $subject = "&#128274; Protected mode request: {$clubName}";
        $message = "A request has been made to <strong>{$actionText} protected mode</strong> for club <strong>{$clubName}</strong>.<br><br>
            When active, player names in the club will only be visible to authenticated members via their personal link.<br><br>
            Click the button below to confirm this change.";
    }

    return sendConfirmationEmail($presidentEmail, $subject, $message, $url);
}

/**
 * Send club access confirmation (for "Sono io" / "Sono il presidente")
 */
function sendClubAccessConfirmation(string $to, string $clubName, string $role, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        if ($role === 'president') {
            $subject = "Conferma identità: presidente di {$clubName}";
            $message = "È stata ricevuta una richiesta di accesso come <strong>presidente</strong> del circolo:<br>
                <div style=\"text-align: center; margin: 1rem 0; font-size: 1.1em; font-weight: bold;\">{$clubName}</div>
                Se sei tu il presidente, clicca il pulsante qui sotto. Riceverai un accesso permanente ai dati del tuo circolo su questo dispositivo.";
        } else {
            $subject = "Conferma identità: giocatore di {$clubName}";
            $message = "È stata ricevuta una richiesta di accesso come <strong>giocatore</strong> del circolo:<br>
                <div style=\"text-align: center; margin: 1rem 0; font-size: 1.1em; font-weight: bold;\">{$clubName}</div>
                Se sei tu questo giocatore, clicca il pulsante qui sotto. Riceverai un accesso permanente ai dati del tuo circolo su questo dispositivo.";
        }
    } else {
        if ($role === 'president') {
            $subject = "Identity confirmation: president of {$clubName}";
            $message = "An access request has been received for the <strong>president</strong> of club:<br>
                <div style=\"text-align: center; margin: 1rem 0; font-size: 1.1em; font-weight: bold;\">{$clubName}</div>
                If you are the president, click the button below. You will receive permanent access to your club's data on this device.";
        } else {
            $subject = "Identity confirmation: player of {$clubName}";
            $message = "An access request has been received for a <strong>player</strong> of club:<br>
                <div style=\"text-align: center; margin: 1rem 0; font-size: 1.1em; font-weight: bold;\">{$clubName}</div>
                If you are this player, click the button below. You will receive permanent access to your club's data on this device.";
        }
    }

    return sendConfirmationEmail($to, $subject, $message, $url);
}

/**
 * Send manual rating request confirmation
 */
function sendManualRatingConfirmation(string $to, string $role, string $playerName, string $circuitName, int $rating, string $category, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    $roleText = [
        'player' => $lang === 'it' ? 'giocatore' : 'player',
        'president' => $lang === 'it' ? 'presidente del circolo' : 'club president',
        'circuit' => $lang === 'it' ? 'responsabile del circuito' : 'circuit manager',
    ];

    if ($lang === 'it') {
        $subject = "⭐ Richiesta variazione manuale: {$playerName}";
        $message = "Come <strong>{$roleText[$role]}</strong>, ti viene chiesto di confermare la seguente variazione manuale:<br><br>
            <strong>Giocatore:</strong> {$playerName}<br>
            <strong>Circuito:</strong> {$circuitName}<br>
            <strong>Nuovo Rating:</strong> {$rating}<br>
            <strong>Categoria:</strong> {$category}<br><br>
            Clicca il pulsante qui sotto per confermare.";
    } else {
        $subject = "⭐ Manual rating request: {$playerName}";
        $message = "As <strong>{$roleText[$role]}</strong>, you are asked to confirm the following manual rating change:<br><br>
            <strong>Player:</strong> {$playerName}<br>
            <strong>Circuit:</strong> {$circuitName}<br>
            <strong>New Rating:</strong> {$rating}<br>
            <strong>Category:</strong> {$category}<br><br>
            Click the button below to confirm.";
    }

    return sendConfirmationEmail($to, $subject, $message, $url);
}

/**
 * Send club info update confirmation to president
 */
function sendClubUpdateConfirmation(string $presidentEmail, string $clubName, string $newName, string $location, string $website, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    $locLine = $location ? ($lang === 'it' ? "<strong>Località:</strong> {$location}" : "<strong>Location:</strong> {$location}") . '<br>' : '';
    $webLine = $website  ? ($lang === 'it' ? "<strong>Sito web:</strong> {$website}"  : "<strong>Website:</strong> {$website}")   . '<br>' : '';

    if ($lang === 'it') {
        $subject = "Richiesta modifica intestazione: {$clubName}";
        $message = "È stata ricevuta una richiesta di modifica dei dati del circolo <strong>{$clubName}</strong>.<br><br>
            <strong>Nuovo nome:</strong> {$newName}<br>
            {$locLine}{$webLine}<br>
            Clicca il pulsante qui sotto per approvare le modifiche.";
    } else {
        $subject = "Club info update request: {$clubName}";
        $message = "A request has been received to update the info for club <strong>{$clubName}</strong>.<br><br>
            <strong>New name:</strong> {$newName}<br>
            {$locLine}{$webLine}<br>
            Click the button below to approve the changes.";
    }

    return sendConfirmationEmail($presidentEmail, $subject, $message, $url);
}

/**
 * Send circuit info update confirmation (name or description) to circuit manager
 */
function sendCircuitUpdateConfirmation(string $managerEmail, string $circuitName, string $field, string $value, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $fieldLabel = $field === 'name' ? 'Nuovo nome' : 'Nuova descrizione';
        $subject = "Richiesta modifica circuito: {$circuitName}";
        $message = "È stata ricevuta una richiesta di modifica per il circuito <strong>{$circuitName}</strong>.<br><br>
            <strong>{$fieldLabel}:</strong> " . htmlspecialchars($value) . "<br><br>
            Clicca il pulsante qui sotto per approvare la modifica.";
    } else {
        $fieldLabel = $field === 'name' ? 'New name' : 'New description';
        $subject = "Circuit update request: {$circuitName}";
        $message = "An update request has been received for circuit <strong>{$circuitName}</strong>.<br><br>
            <strong>{$fieldLabel}:</strong> " . htmlspecialchars($value) . "<br><br>
            Click the button below to approve the change.";
    }

    return sendConfirmationEmail($managerEmail, $subject, $message, $url);
}

/**
 * Send circuit formula change confirmation to circuit manager
 */
function sendCircuitFormulaConfirmation(string $managerEmail, string $circuitName, string $formulaLabel, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Richiesta cambio formula: {$circuitName}";
        $message = "È stata ricevuta una richiesta di cambio formula per il circuito <strong>{$circuitName}</strong>.<br><br>
            <strong>Nuova formula:</strong> {$formulaLabel}<br><br>
            Clicca il pulsante qui sotto per approvare la modifica.";
    } else {
        $subject = "Formula change request: {$circuitName}";
        $message = "A formula change request has been received for circuit <strong>{$circuitName}</strong>.<br><br>
            <strong>New formula:</strong> {$formulaLabel}<br><br>
            Click the button below to approve the change.";
    }

    return sendConfirmationEmail($managerEmail, $subject, $message, $url);
}

// ─────────────────────────────────────────────────────────────────────────────
// Chess School Platform - Instructor account emails
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Send a plain (non-confirmation) notification email using the same template.
 */
function sendInstructorVerification(string $email, string $name, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=verify&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma il tuo account istruttore";
        $message = "Ciao <strong>{$name}</strong>,<br><br>il tuo account istruttore è pronto.<br><br>Conferma il tuo indirizzo email con il pulsante qui sotto per attivare tutte le funzioni della piattaforma.";
        $button  = "Conferma la mia email";
    } else {
        $subject = "Confirm your instructor account";
        $message = "Hi <strong>{$name}</strong>,<br><br>your instructor account is ready.<br><br>Confirm your email address with the button below to unlock all platform features.";
        $button  = "Confirm my email";
    }

    return sendConfirmationEmail($email, $subject, $message, $url, MAIL_BUTTON_COLOR, $button);
}

/**
 * Password reset link for an instructor.
 */
function sendInstructorPasswordReset(string $email, string $name, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=reset_password&token=' . $token;

    if ($lang === 'it') {
        $subject = "Reimposta la tua password";
        $message = "Ciao <strong>{$name}</strong>,<br><br>abbiamo ricevuto una richiesta di reimpostazione della password del tuo account istruttore.<br><br>Scegli una nuova password con il pulsante qui sotto. Se non hai richiesto tu questa operazione, puoi ignorare questa email.";
        $button  = "Scegli una nuova password";
    } else {
        $subject = "Reset your password";
        $message = "Hi <strong>{$name}</strong>,<br><br>we received a request to reset the password of your instructor account.<br><br>Choose a new password with the button below. If you did not request this, you can safely ignore this email.";
        $button  = "Choose a new password";
    }

    return sendConfirmationEmail($email, $subject, $message, $url, MAIL_BUTTON_DANGER, $button);
}

/**
 * Invite a student (or their guardian) to a class.
 */
function sendStudentClassInvite(string $email, string $studentName, string $className, string $instructorName): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/';

    if ($lang === 'it') {
        $subject = "Iscrizione al corso: {$className}";
        $message = "Ciao,<br><br><strong>{$instructorName}</strong> ha iscritto <strong>{$studentName}</strong> al corso <strong>{$className}</strong>.<br><br>Riceverai qui gli aggiornamenti relativi al corso.";
        $button  = "Vai a OpenELO";
    } else {
        $subject = "Enrolled in class: {$className}";
        $message = "Hi,<br><br><strong>{$instructorName}</strong> enrolled <strong>{$studentName}</strong> in the class <strong>{$className}</strong>.<br><br>You will receive class updates at this address.";
        $button  = "Go to OpenELO";
    }

    return sendConfirmationEmail($email, $subject, $message, $url, MAIL_BUTTON_INFO, $button);
}
