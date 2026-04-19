<?php
/**
 * OpenElo - Email Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

/**
 * Get email subject prefix: always "OpenELO - ", with env tag for non-production
 */
function getEmailSubjectPrefix(): string {
    if (APP_ENV === '') return 'OpenELO - ';
    return 'OpenELO [' . strtoupper(APP_ENV) . '] - ';
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail(string $to, string $subject, string $message, string $confirmUrl, string $buttonColor = '#4361ee'): bool {
    $subject = getEmailSubjectPrefix() . $subject;
    $lang = getCurrentLang();

    $htmlMessage = "
    <!DOCTYPE html>
    <html lang='{$lang}'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.7; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a2e; color: white; padding: 24px 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .header img { height: 40px; width: 40px; vertical-align: middle; margin-right: 10px; }
            .header-title { display: inline-block; vertical-align: middle; font-size: 1.6rem; font-weight: 700; letter-spacing: 0.5px; }
            .header-title span { color: #4361ee; }
            .content { background: #f8f9fa; padding: 32px; border-radius: 0 0 8px 8px; font-size: 16px; }
            .content p { margin: 0 0 16px 0; font-size: 16px; }
            .button { display: inline-block; background: {$buttonColor}; color: white !important; padding: 14px 36px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: bold; font-size: 16px; }
            .footer { text-align: center; color: #666; font-size: 13px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='https://raw.githubusercontent.com/francescobianco/openelo/main/public/logo.png' alt='OpenELO' width='40' height='40'>
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

    return sendConfirmationEmail($to, $subject, $message, $url, '#dc2626');
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
        $preview = $field === 'description' ? '<br><em style="font-size:13px;color:#666;">' . htmlspecialchars(mb_substr($value, 0, 200)) . (mb_strlen($value) > 200 ? '…' : '') . '</em>' : '';
        $message = "È stata ricevuta una richiesta di modifica per il circuito <strong>{$circuitName}</strong>.<br><br>
            <strong>{$fieldLabel}:</strong> " . htmlspecialchars($value) . "{$preview}<br><br>
            Clicca il pulsante qui sotto per approvare la modifica.";
    } else {
        $fieldLabel = $field === 'name' ? 'New name' : 'New description';
        $subject = "Circuit update request: {$circuitName}";
        $preview = $field === 'description' ? '<br><em style="font-size:13px;color:#666;">' . htmlspecialchars(mb_substr($value, 0, 200)) . (mb_strlen($value) > 200 ? '…' : '') . '</em>' : '';
        $message = "An update request has been received for circuit <strong>{$circuitName}</strong>.<br><br>
            <strong>{$fieldLabel}:</strong> " . htmlspecialchars($value) . "{$preview}<br><br>
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
