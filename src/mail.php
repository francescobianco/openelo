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
