<?php
/**
 * OpenElo - Email Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

/**
 * Send confirmation email
 * In production, use a proper mail library like PHPMailer
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
            .button { display: inline-block; background: #4361ee; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .button:hover { background: #3a56d4; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>OpenElo</h1>
            </div>
            <div class='content'>
                <p>{$message}</p>
                <p style='text-align: center;'>
                    <a href='{$confirmUrl}' class='button'>" . __('form_submit') . "</a>
                </p>
                <p style='font-size: 12px; color: #666;'>
                    " . ($lang === 'it' ? 'Oppure copia questo link:' : 'Or copy this link:') . "<br>
                    <code>{$confirmUrl}</code>
                </p>
            </div>
            <div class='footer'>
                <p>OpenElo - " . __('site_tagline') . "</p>
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
        $logFile = $logDir . '/' . date('Y-m-d_H-i-s') . '_' . md5($to . time()) . '.html';
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
 * Send club creation confirmation
 */
function sendClubConfirmation(string $email, string $clubName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma creazione circolo: {$clubName}";
        $message = "Hai richiesto di creare il circolo <strong>{$clubName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare e attivare il circolo.";
    } else {
        $subject = "Confirm club creation: {$clubName}";
        $message = "You requested to create the club <strong>{$clubName}</strong>.<br><br>Click the button below to confirm and activate the club.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}

/**
 * Send club join circuit request to circuit owner
 */
function sendCircuitJoinRequest(string $ownerEmail, string $clubName, string $circuitName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Richiesta adesione circuito: {$clubName}";
        $message = "Il circolo <strong>{$clubName}</strong> ha richiesto di aderire al circuito <strong>{$circuitName}</strong>.<br><br>Clicca il pulsante qui sotto per approvare l'adesione.";
    } else {
        $subject = "Circuit join request: {$clubName}";
        $message = "The club <strong>{$clubName}</strong> has requested to join the circuit <strong>{$circuitName}</strong>.<br><br>Click the button below to approve the membership.";
    }

    return sendConfirmationEmail($ownerEmail, $subject, $message, $url);
}

/**
 * Send player registration confirmation
 */
function sendPlayerConfirmation(string $email, string $playerName, string $token): bool {
    $lang = getCurrentLang();
    $url = BASE_URL . '/?page=confirm&token=' . $token;

    if ($lang === 'it') {
        $subject = "Conferma registrazione giocatore";
        $message = "È stata richiesta la registrazione del giocatore <strong>{$playerName}</strong>.<br><br>Clicca il pulsante qui sotto per confermare.";
    } else {
        $subject = "Confirm player registration";
        $message = "Registration has been requested for player <strong>{$playerName}</strong>.<br><br>Click the button below to confirm.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
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
        $subject = "Conferma partita: {$white} vs {$black}";
        $message = "Come <strong>{$roleText}</strong>, ti viene chiesto di confermare il seguente risultato:<br><br>
            <strong>Circuito:</strong> {$circuit}<br>
            <strong>Bianco:</strong> {$white}<br>
            <strong>Nero:</strong> {$black}<br>
            <strong>Risultato:</strong> {$result}<br><br>
            Clicca il pulsante qui sotto per confermare.";
    } else {
        $roleText = $role === 'president' ? 'club president' : 'player';
        $subject = "Confirm match: {$white} vs {$black}";
        $message = "As <strong>{$roleText}</strong>, you are asked to confirm the following result:<br><br>
            <strong>Circuit:</strong> {$circuit}<br>
            <strong>White:</strong> {$white}<br>
            <strong>Black:</strong> {$black}<br>
            <strong>Result:</strong> {$result}<br><br>
            Click the button below to confirm.";
    }

    return sendConfirmationEmail($email, $subject, $message, $url);
}
