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

    $logoBase64 = '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';

    $htmlMessage = "
    <!DOCTYPE html>
    <html lang='{$lang}'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.7; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a2e; color: white; padding: 24px 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .header img { height: 48px; width: 48px; vertical-align: middle; margin-right: 10px; }
            .header-title { display: inline-block; vertical-align: middle; font-size: 1.6rem; font-weight: 700; letter-spacing: 0.5px; }
            .header-title span { color: #4361ee; }
            .content { background: #f8f9fa; padding: 32px; border-radius: 0 0 8px 8px; font-size: 16px; }
            .content p { margin: 0 0 16px 0; font-size: 16px; }
            .button { display: inline-block; background: #4361ee; color: white !important; padding: 14px 36px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; font-size: 16px; }
            .footer { text-align: center; color: #666; font-size: 13px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='data:image/png;base64,{$logoBase64}' alt='OpenELO'>
                <span class='header-title'>Open<span>ELO</span></span>
            </div>
            <div class='content'>
                <p>{$message}</p>
                <p style='text-align: center;'>
                    <a href='{$confirmUrl}' class='button'>" . ($lang === 'it' ? 'Conferma' : 'Confirm') . "</a>
                </p>
                <p style='font-size: 13px; color: #666;'>
                    " . ($lang === 'it' ? 'Oppure copia questo link:' : 'Or copy this link:') . "<br>
                    <code style='font-size: 12px; word-break: break-all;'>{$confirmUrl}</code>
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
        $roleText = [
            'president' => 'presidente del circolo',
            'circuit_manager' => 'responsabile del circuito',
            'player' => 'giocatore'
        ][$role] ?? 'giocatore';
        $subject = "♔♕ Conferma partita: {$white} vs {$black}";
        $message = "♟ Come <strong>{$roleText}</strong>, ti viene chiesto di confermare il seguente risultato:<br><br>
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
