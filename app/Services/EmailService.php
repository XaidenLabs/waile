<?php

namespace App\Services;

use App\Models\SettingModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Sends mail via PHPMailer using SMTP settings stored in the database
 * (group = 'smtp'), configured by the platform owner under Admin → Email / SMTP.
 *
 *   $mail = new EmailService();
 *   if (! $mail->send('user@example.com', 'Subject', '<p>HTML body</p>')) {
 *       echo $mail->lastError; // why it failed
 *   }
 *
 * The SMTP password is stored encrypted (AES-256) and decrypted here at send time.
 */
class EmailService
{
    /** Last PHPMailer error (empty when the last send succeeded). */
    public string $lastError = '';

    private array $cfg;

    public function __construct()
    {
        $this->cfg = (new SettingModel())->getGroup('smtp');
    }

    /**
     * True once the essential settings are present.
     */
    public function isConfigured(): bool
    {
        if (empty($this->cfg['from_email'])) {
            return false;
        }

        // PHP mail() needs no host; SMTP does.
        if (($this->cfg['protocol'] ?? 'smtp') === 'mail') {
            return true;
        }

        return ! empty($this->cfg['host']);
    }

    /**
     * Send an HTML email. Returns true on success; check $this->lastError on failure.
     */
    public function send(string $to, string $subject, string $htmlBody, array $opts = []): bool
    {
        $this->lastError = '';

        if (! $this->isConfigured()) {
            $this->lastError = 'SMTP is not configured.';
            log_message('error', 'EmailService: ' . $this->lastError);
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            if (($this->cfg['protocol'] ?? 'smtp') === 'mail') {
                $mail->isMail();
            } else {
                $mail->isSMTP();
                $mail->Host    = $this->cfg['host'] ?? '';
                $mail->Port    = (int) ($this->cfg['port'] ?? 587);
                $mail->Timeout = 15;

                $username = $this->cfg['username'] ?? '';
                if ($username !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = $username;
                    $mail->Password = $this->password();
                }

                $mail->SMTPSecure = match ($this->cfg['encryption'] ?? 'tls') {
                    'ssl'   => PHPMailer::ENCRYPTION_SMTPS,    // implicit SSL (port 465)
                    'tls'   => PHPMailer::ENCRYPTION_STARTTLS, // STARTTLS (port 587)
                    default => '',                            // none
                };
                // Don't auto-upgrade to TLS when the admin explicitly chose "none".
                $mail->SMTPAutoTLS = ($this->cfg['encryption'] ?? 'tls') !== '';
            }

            $mail->setFrom($this->cfg['from_email'], $this->cfg['from_name'] ?? 'WALE');
            $mail->addAddress($to);
            if (! empty($opts['reply_to'])) {
                $mail->addReplyTo($opts['reply_to']);
            }

            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = trim(strip_tags($htmlBody));

            $mail->send();
            return true;
        } catch (PHPMailerException|\Throwable $e) {
            $this->lastError = $mail->ErrorInfo ?: $e->getMessage();
            log_message('error', 'EmailService (PHPMailer) send failed: ' . $this->lastError);
            return false;
        }
    }

    /**
     * Send a diagnostic test email. Returns ['success' => bool, 'message' => string].
     */
    public function sendTest(string $to): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => lang('Messages.smtpNotConfigured')];
        }

        $body = '<div style="font-family:sans-serif;font-size:15px;color:#2d3748">'
              . '<h2 style="color:#128C7E">' . lang('Emails.smtpWorkingHeading') . '</h2>'
              . '<p>' . lang('Emails.smtpTestBody', [esc(app_name())]) . '</p>'
              . '<hr><p style="color:#718096;font-size:12px">' . lang('Emails.smtpSentFrom', [esc(app_name())]) . '</p>'
              . '</div>';

        if ($this->send($to, lang('Emails.subjSmtpTest', [app_name()]), $body)) {
            return ['success' => true, 'message' => lang('Emails.smtpTestSent', [$to])];
        }

        // Surface PHPMailer's real reason so config problems are easy to fix.
        return ['success' => false, 'message' => lang('Messages.smtpCouldNotSend', [$this->lastError ?: lang('Messages.smtpUnknownError')])];
    }

    // ------------------------------------------------------------------

    /**
     * Decrypt the stored SMTP password.
     *
     * We try to decrypt and fall back to the raw value if that fails. This is
     * more reliable than a length heuristic: SMTP passwords are often short, so
     * their encrypted form can be shorter than EncryptionService::isEncrypted()'s
     * threshold and would otherwise be mistaken for plaintext.
     */
    private function password(): string
    {
        $stored = $this->cfg['password'] ?? '';
        if ($stored === '') {
            return '';
        }

        try {
            return (new EncryptionService())->decrypt($stored);
        } catch (\Throwable) {
            return $stored; // not encrypted (e.g. a legacy plaintext value)
        }
    }
}
