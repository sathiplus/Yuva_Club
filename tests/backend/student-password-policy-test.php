<?php
declare(strict_types=1);

require_once __DIR__ . '/../../portal-lib.php';

function student_password_policy_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

student_password_policy_assert(
    student_password_policy_error('Ab1!xyz') !== '',
    'Student policy must reject passwords shorter than eight characters.'
);
student_password_policy_assert(
    student_password_policy_error('Ab1!xyza') === '',
    'Student policy must accept an eight-character password with all required character classes.'
);
student_password_policy_assert(
    student_password_policy_error('CorrectPass!2026') === '',
    'Student policy must preserve valid existing passwords longer than eight characters.'
);

foreach ([
    'lowercase' => 'ab1!xyza',
    'uppercase' => 'AB1!XYZA',
    'number' => 'Abc!xyza',
    'special character' => 'Ab12xyza',
] as $requirement => $password) {
    student_password_policy_assert(
        student_password_policy_error($password) !== '',
        'Student policy must still require a ' . $requirement . '.'
    );
}

student_password_policy_assert(
    password_policy_error('Ab1!abcdefg') !== '',
    'Administrative policy must continue rejecting eleven-character passwords.'
);
student_password_policy_assert(
    password_policy_error('Ab1!abcdefgh') === '',
    'Administrative policy must continue accepting twelve-character complex passwords.'
);

echo "Student password policy tests passed.\n";
