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
        $emailMessage = "
        <!DOCTYPE html>
        <html lang='{$lang}'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a1a2e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .field { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
                .label { font-weight: bold; color: #4361ee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>♞ OpenELO Contact Form</h1>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>" . ($lang === 'it' ? 'Nome' : 'Name') . ":</div>
                        <div>" . htmlspecialchars($name) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Email:</div>
                        <div>" . htmlspecialchars($email) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>" . ($lang === 'it' ? 'Oggetto' : 'Subject') . ":</div>
                        <div>" . htmlspecialchars($subject) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>" . ($lang === 'it' ? 'Messaggio' : 'Message') . ":</div>
                        <div>" . nl2br(htmlspecialchars($body)) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

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
