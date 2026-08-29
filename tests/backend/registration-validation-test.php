<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$submit = file_get_contents($root . '/submit-registration.php');
$form = file_get_contents($root . '/registration.php');
$lib = file_get_contents($root . '/portal-lib.php');

function registration_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS $message\n";
}

registration_assert(str_contains($submit, "'weak-password'"), 'weak password reason is distinct');
registration_assert(str_contains($submit, "'password-mismatch'"), 'mismatched password reason is distinct');
foreach ([
    'incomplete-schedule-pair', 'missing-required-field', 'invalid-or-missing-age',
    'age-below-minimum', 'age-above-maximum', 'missing-agreement',
    'persistence-failure', 'empty-generated-student-id',
] as $reason) {
    registration_assert(str_contains($submit, "'$reason'") || str_contains($form, "'$reason'"), "$reason reason is wired");
}
registration_assert(str_contains($submit, "DateTimeImmutable::createFromFormat('!Y-m-d', \$dateOfBirth)"), 'date of birth is authoritative');
registration_assert(str_contains($submit, "registration_validation_redirect('age-below-minimum')"), 'underage validation is preserved');
registration_assert(str_contains($submit, "registration_validation_redirect('age-above-maximum')"), 'overage validation is preserved');
registration_assert(str_contains($submit, "registration_validation_redirect('missing-agreement')"), 'agreement validation is distinct');
registration_assert(str_contains($submit, "registration_validation_redirect('incomplete-schedule-pair')"), 'schedule pair validation is distinct');
registration_assert(!str_contains($lib, "'account_password'"), 'flash allowlist does not preserve passwords');
registration_assert(str_contains($submit, 'unset($_SESSION[\'csrf_token\'])'), 'validation redirect rotates CSRF token');
registration_assert(str_contains($form, 'registration_flash_take()'), 'form consumes server-side flash values');
registration_assert(str_contains($form, 'preferred_time_1') && str_contains($form, 'schedule_error_1'), 'client schedule feedback is inline');
registration_assert(str_contains($form, 'registrationFlash'), 'non-sensitive values are restored client-side');
registration_assert(str_contains($submit, 'create_registration_with_reserved_yuva_id($registrationInput)'), 'SQL persistence and reservation call remains present');
registration_assert(str_contains($submit, 'append_registration_row($csvPath'), 'filesystem persistence call remains present');
registration_assert(str_contains($submit, 'isset($_GET[\'health\'])'), 'health endpoint remains present');

echo "Registration validation contract tests passed\n";
