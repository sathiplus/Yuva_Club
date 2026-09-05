<?php
declare(strict_types=1);

/** Uses the configured canonical URL, never the request Host or a secret URL. */
function beta_invitation_redemption_url(): string
{
    $base = rtrim(app_url(), '/');
    if (!in_array($base, ['https://yuvaclub.app', 'https://www.yuvaclub.app'], true)) {
        throw new RuntimeException('An official production application URL is required for Beta invitations.');
    }
    return $base . '/portal.php#app-profile';
}

/** The returned body is transient mail-provider input only; never log or persist it. */
function beta_invitation_email(string $code, string $expiresAt, string $url): array
{
    if (!preg_match('/^YUVA-BETA-[A-F0-9]{24}$/D', $code)
        || !in_array($url, ['https://yuvaclub.app/portal.php#app-profile', 'https://www.yuvaclub.app/portal.php#app-profile'], true)) {
        throw new RuntimeException('Invalid invitation email configuration.');
    }
    $expiry = (new DateTimeImmutable($expiresAt, new DateTimeZone('UTC')))->format('Y-m-d H:i') . ' UTC';
    $text = "You have been invited to join the YUVA Club Beta.\n\n"
        . "Your Beta access includes Premium features for the campaign's invitation period.\n\n"
        . "Single-use invitation code: {$code}\n"
        . "Activate Beta Access: {$url}\n\n"
        . "Sign in to your invited Student account, then enter this code in the Premium beta invitation form under Profile. "
        . "If sign-in takes you to Home, open Profile. The code expires at {$expiry}, or earlier if the campaign ends or the invitation is revoked.\n\n"
        . "This invitation is unique to your account and can only be used once. Do not share the code.\n"
        . "If you were not expecting this invitation, you can ignore this email.\n\nYUVA Club\nyuvaclub.app";
    $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<p>You have been invited to join the YUVA Club Beta.</p>'
        . '<p>Your Beta access includes Premium features for the campaign&#39;s invitation period.</p>'
        . '<p>Single-use invitation code: <strong>' . $escape($code) . '</strong></p>'
        . '<p><a href="' . $escape($url) . '">Activate Beta Access</a></p>'
        . '<p>Sign in to your invited Student account, then enter this code in the Premium beta invitation form under Profile. If sign-in takes you to Home, open Profile.</p>'
        . '<p>The code expires at ' . $escape($expiry) . ', or earlier if the campaign ends or the invitation is revoked.</p>'
        . '<p>This invitation is unique to your account and can only be used once. Do not share the code.</p>'
        . '<p>If you were not expecting this invitation, you can ignore this email.</p><p>YUVA Club<br>yuvaclub.app</p>';
    return ['subject' => "You're invited to YUVA Club Beta", 'text' => $text, 'html' => $html];
}
