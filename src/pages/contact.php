<?php
/**
 * OpenElo - Contact Page
 */

require_once SRC_PATH . '/mail.php';

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($body)) {
            throw new Exception(__('error_required'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception($lang === 'it' ? 'Email non valida' : 'Invalid email');
        }

        // Send email to admin
        $emailSubject = getEmailSubjectPrefix() . "📧 Contact: " . $subject;
        $font = "'Nunito','Trebuchet MS',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif";
        $logoUrl = 'https://raw.githubusercontent.com/francescobianco/openelo/main/public/logo.png';

        $fields = [
            ($lang === 'it' ? 'Nome' : 'Name')       => htmlspecialchars($name),
            'Email'                                  => htmlspecialchars($email),
            ($lang === 'it' ? 'Oggetto' : 'Subject') => htmlspecialchars($subject),
            ($lang === 'it' ? 'Messaggio' : 'Message') => nl2br(htmlspecialchars($body)),
        ];

        $rows = '';
        foreach ($fields as $label => $value) {
            $rows .= <<<ROW
                            <tr>
                                <td style="padding:0 0 14px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F9FC; border-radius:12px;">
                                        <tr>
                                            <td style="padding:14px 18px; font-family:{$font}; font-size:15px; line-height:1.6; color:#21374D; font-weight:600;">
                                                <span style="display:block; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#647F97; margin-bottom:4px;">{$label}</span>
                                                {$value}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
ROW;
        }

        $title = $lang === 'it' ? 'Nuovo messaggio dal form contatti' : 'New message from the contact form';

        $emailMessage = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{$title}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap">
    <style>
        body { margin: 0; padding: 0; background: #F1F6FA; }
    </style>
</head>
<body style="margin:0; padding:0; background:#F1F6FA; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F6FA;">
        <tr>
            <td align="center" style="padding:28px 14px 34px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background:#FFFFFF; border-radius:20px; border:1px solid #DBE6EF;">

                    <tr>
                        <td align="center" style="padding:30px 24px 22px;">
                            <img src="{$logoUrl}" alt="OpenELO" width="46" height="46" style="display:inline-block; vertical-align:middle; border:0; border-radius:12px;">
                            <span style="display:inline-block; vertical-align:middle; margin-left:10px; font-family:{$font}; font-size:26px; font-weight:900; letter-spacing:-0.5px; color:#21374D;">Open<span style="color:#FF9E1B;">ELO</span></span>
                        </td>
                    </tr>

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

                    <tr>
                        <td style="padding:30px 34px 10px; font-family:{$font}; font-size:19px; font-weight:900; color:#21374D;">
                            {$title}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 34px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
{$rows}
                            </table>
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
            'Reply-To: ' . $email,
        ];

        // For development, log emails instead of sending
        if (SMTP_HOST === 'smtp.example.com') {
            $logDir = dirname(dirname(__DIR__)) . '/data/emails';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/' . date('Y-m-d_H-i-s') . '_contact.html';
            file_put_contents($logFile, "To: " . ADMIN_EMAIL . "\nSubject: {$emailSubject}\n\n{$emailMessage}");
            $success = true;
        } else {
            $success = mail(ADMIN_EMAIL, $emailSubject, $emailMessage, implode("\r\n", $headers));
        }

        if ($success) {
            $message = $lang === 'it'
                ? 'Messaggio inviato con successo! Ti risponderemo al più presto.'
                : 'Message sent successfully! We will respond as soon as possible.';
            $messageType = 'success';
            // Clear form
            $_POST = [];
        } else {
            throw new Exception($lang === 'it' ? 'Errore durante l\'invio' : 'Error sending message');
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1><?= $lang === 'it' ? 'Contattaci' : 'Contact Us' ?></h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width: 700px; margin: 0 auto;">
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            <?= $lang === 'it'
                ? 'Hai domande, suggerimenti o hai bisogno di assistenza? Compila il form qui sotto e ti risponderemo al più presto.'
                : 'Have questions, suggestions, or need assistance? Fill out the form below and we\'ll get back to you as soon as possible.'
            ?>
        </p>

        <form method="POST">
            <div class="form-group">
                <label for="name"><?= $lang === 'it' ? 'Nome' : 'Name' ?> *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="subject"><?= $lang === 'it' ? 'Oggetto' : 'Subject' ?> *</label>
                <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="message"><?= $lang === 'it' ? 'Messaggio' : 'Message' ?> *</label>
                <textarea id="message" name="message" rows="8" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <?= $lang === 'it' ? 'Invia Messaggio' : 'Send Message' ?>
            </button>
        </form>
    </div>
</div>
