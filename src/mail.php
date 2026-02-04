<?php
/**
 * OpenElo - Email Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

/**
 * Send confirmation email
 */
function sendConfirmationEmail(string $to, string $subject, string $message, string $confirmUrl): bool {
    $lang = getCurrentLang();

    $htmlMessage = "
    <!DOCTYPE html>
    <html lang='{$lang}'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a2e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #4361ee; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>♞ OpenELO ♞</h1>
            </div>
            <div class='content'>
                <p>{$message}</p>
                <p style='text-align: center;'>
                    <a href='{$confirmUrl}' class='button'>" . ($lang === 'it' ? 'Conferma' : 'Confirm') . "</a>
                </p>
                <p style='font-size: 12px; color: #666;'>
                    " . ($lang === 'it' ? 'Oppure copia questo link:' : 'Or copy this link:') . "<br>
                    <code>{$confirmUrl}</code>
                </p>
            </div>
            <div class='footer'>
                <p>OpenELO - " . __('site_tagline') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

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
        $subject = "♔ Conferma creazione circuito: {$circuitName}";
        $message = "♚ Hai richiesto di creare il circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare e attivare il circuito.";
    } else {
        $subject = "♔ Confirm circuit creation: {$circuitName}";
        $message = "♚ You requested to create the circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to confirm and activate the circuit.";
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
        $subject = "♖ Conferma creazione circolo: {$clubName}";
        $message = "♟ Hai richiesto di creare il circolo <strong>{$clubName}</strong> nel circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare. Il responsabile del circuito dovrà anche approvare l'adesione.";
    } else {
        $subject = "♖ Confirm club creation: {$clubName}";
        $message = "♟ You requested to create the club <strong>{$clubName}</strong> in circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to confirm. The circuit manager will also need to approve the membership.";
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
        $subject = "♖ Richiesta adesione circolo: {$clubName}";
        $message = "♟ Il circolo <strong>{$clubName}</strong> ha richiesto di aderire al circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare l'adesione.";
    } else {
        $subject = "♖ Club membership request: {$clubName}";
        $message = "♟ The club <strong>{$clubName}</strong> has requested to join the circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to approve the membership.";
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
        $subject = "♟ Conferma registrazione giocatore";
        $message = "♟ È stata richiesta la tua registrazione come giocatore <strong>{$playerName}</strong> nel circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare. Il presidente del circolo dovrà anche approvare.";
    } else {
        $subject = "♟ Confirm player registration";
        $message = "♟ Registration has been requested for you as player <strong>{$playerName}</strong> in club <strong>{$clubName}</strong>.<br><br>Click the button below to confirm. The club president will also need to approve.";
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
        $subject = "♟ Approvazione nuovo giocatore: {$playerName}";
        $message = "♟ Il giocatore <strong>{$playerName}</strong> ha richiesto di unirsi al circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare l'iscrizione.";
    } else {
        $subject = "♟ Approve new player: {$playerName}";
        $message = "♟ Player <strong>{$playerName}</strong> has requested to join club <strong>{$clubName}</strong>.<br><br>Click the button below to approve the registration.";
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
        $roleText = $role === 'president' ? 'presidente del circolo' : 'giocatore';
        $subject = "♔♕ Conferma partita: {$white} vs {$black}";
        $message = "♟ Come <strong>{$roleText}</strong>, ti viene chiesto di confermare il seguente risultato:<br><br>
            <strong>Circuito:</strong> {$circuit}<br>
            <strong>♔ Bianco:</strong> {$white}<br>
            <strong>♚ Nero:</strong> {$black}<br>
            <strong>Risultato:</strong> {$result}<br><br>
            Clicca il pulsante qui sotto per confermare.";
    } else {
        $roleText = $role === 'president' ? 'club president' : 'player';
        $subject = "♔♕ Confirm match: {$white} vs {$black}";
        $message = "♟ As <strong>{$roleText}</strong>, you are asked to confirm the following result:<br><br>
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
function sendDeletionRequest(string $to, string $entityType, string $entityName, string $requesterEmail, string $reason, int $requestId): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=deletion&id=' . $requestId;

    $entityTypeText = [
        'circuit' => $lang === 'it' ? 'circuito' : 'circuit',
        'club' => $lang === 'it' ? 'circolo' : 'club',
        'player' => $lang === 'it' ? 'giocatore' : 'player',
        'match' => $lang === 'it' ? 'partita' : 'match',
    ];

    if ($lang === 'it') {
        $subject = "🗑 Richiesta eliminazione: {$entityName}";
        $message = "È stata ricevuta una richiesta di eliminazione per il {$entityTypeText[$entityType]} <strong>{$entityName}</strong>.<br><br>
            <strong>Richiedente:</strong> {$requesterEmail}<br>
            <strong>Motivo:</strong> {$reason}<br><br>
            Clicca il pulsante qui sotto per approvare o rifiutare la richiesta.";
    } else {
        $subject = "🗑 Deletion request: {$entityName}";
        $message = "A deletion request has been received for the {$entityTypeText[$entityType]} <strong>{$entityName}</strong>.<br><br>
            <strong>Requester:</strong> {$requesterEmail}<br>
            <strong>Reason:</strong> {$reason}<br><br>
            Click the button below to approve or reject the request.";
    }

    return sendConfirmationEmail($to, $subject, $message, $url);
}
