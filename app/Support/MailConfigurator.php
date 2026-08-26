<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/**
 * Applies the SMTP credentials stored in settings over the compiled mail config.
 *
 * A school changes hosting far more often than it edits .env, so the credentials
 * live in the settings table and are pushed into the mailer at runtime. Applying
 * this on demand rather than at boot keeps a bad configuration from breaking
 * every request — only the send fails, and only once it is attempted.
 */
class MailConfigurator
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /**
     * Whether the committee has switched outgoing mail on and given it a host.
     */
    public function enabled(): bool
    {
        if ($this->settings->get('mail_enabled') !== '1') {
            return false;
        }

        return $this->mailer() !== 'smtp' || filled($this->settings->get('mail_host'));
    }

    public function requiresVerification(): bool
    {
        return $this->enabled() && $this->settings->get('mail_require_verification') === '1';
    }

    /**
     * Push the stored credentials into the mail config and forget any mailer
     * already built from the previous values.
     */
    public function apply(): void
    {
        $mailer = $this->mailer();

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => $this->settings->get('mail_host'),
            'mail.mailers.smtp.port' => (int) ($this->settings->get('mail_port') ?: 587),
            'mail.mailers.smtp.username' => $this->settings->get('mail_username') ?: null,
            'mail.mailers.smtp.password' => $this->password(),
            'mail.mailers.smtp.encryption' => $this->encryption(),
            'mail.from.address' => $this->fromAddress(),
            'mail.from.name' => $this->fromName(),
        ]);

        // Mail manager caches a mailer per name; without this the next send
        // would reuse the transport built from the old credentials.
        Mail::purge($mailer);
    }

    public function fromAddress(): string
    {
        return $this->settings->get('mail_from_address')
            ?: $this->settings->get('school_email')
            ?: (string) config('mail.from.address');
    }

    public function fromName(): string
    {
        return $this->settings->get('mail_from_name')
            ?: $this->settings->schoolName();
    }

    /**
     * Stored encrypted, but tolerate a plaintext value so a password written
     * directly into the database still works instead of throwing.
     */
    public function password(): ?string
    {
        $stored = $this->settings->raw('mail_password');

        if (blank($stored)) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return $stored;
        }
    }

    public function encrypt(string $password): string
    {
        return Crypt::encryptString($password);
    }

    private function mailer(): string
    {
        $mailer = $this->settings->get('mail_mailer') ?: 'smtp';

        return in_array($mailer, ['smtp', 'log', 'array'], true) ? $mailer : 'smtp';
    }

    private function encryption(): ?string
    {
        $value = strtolower((string) $this->settings->get('mail_encryption'));

        return in_array($value, ['tls', 'ssl'], true) ? $value : null;
    }
}
