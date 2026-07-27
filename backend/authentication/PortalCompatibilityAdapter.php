<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

final class PortalCompatibilityAdapter
{
    /** @var array<int, string> */
    private const LEGACY_HEADERS = [
        'Submitted At',
        'Yuva Club ID',
        'Student First Name',
        'Student Last Name',
        'Preferred Name',
        'Date of Birth',
        'Age',
        'Program Group',
        'Grade',
        'School',
        'City/State',
        'Parent/Guardian Name',
        'Relationship',
        'Parent Email',
        'Parent Phone Number',
        'Student Email',
        'Student Phone Number',
        'WhatsApp Username / Number',
        'Interests',
        'Why Join',
        'Presentation Experience',
        'Presentation Topics',
        'Preferred Schedule',
        'Suggestions',
        'Code of Conduct Agreement',
        'Recording Agreement',
        'Parent Permission',
        'IP Address',
    ];

    /** @return array<int, string> */
    public function legacyHeaders(): array
    {
        return self::LEGACY_HEADERS;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    public function studentToLegacyRecord(array $row): array
    {
        $record = array_fill_keys(self::LEGACY_HEADERS, '');
        $studentEmail = $this->usableEmail($row['student_email'] ?? null);

        $record['Submitted At'] = $this->stringValue($row['registration_submitted_at'] ?? null);
        $record['Yuva Club ID'] = $this->stringValue($row['yuva_id'] ?? null);
        $record['Student First Name'] = $this->stringValue($row['student_first_name'] ?? null);
        $record['Student Last Name'] = $this->stringValue($row['student_last_name'] ?? null);
        $record['Preferred Name'] = $this->stringValue($row['preferred_name'] ?? null);
        $record['Date of Birth'] = $this->dateValue($row['date_of_birth'] ?? null);
        $record['Age'] = $this->stringValue($row['age'] ?? null);
        $record['Program Group'] = $this->stringValue($row['program_name'] ?? null);
        $record['Grade'] = $this->stringValue($row['grade'] ?? null);
        $record['School'] = $this->stringValue($row['school'] ?? null);
        $record['City/State'] = $this->stringValue($row['city_state'] ?? null);
        $record['Parent/Guardian Name'] = $this->stringValue($row['parent_name'] ?? null);
        $record['Relationship'] = $this->stringValue($row['parent_relationship'] ?? null);
        $record['Parent Email'] = $this->usableEmail($row['parent_email'] ?? null);
        $record['Parent Phone Number'] = $this->stringValue($row['parent_phone'] ?? null);
        $record['Student Email'] = $studentEmail;
        $record['Student Phone Number'] = $this->stringValue($row['student_phone'] ?? null);
        $record['WhatsApp Username / Number'] = $this->stringValue($row['whatsapp_contact'] ?? null);
        $record['Interests'] = $this->stringValue($row['interests'] ?? null);
        $record['Why Join'] = $this->stringValue($row['why_join'] ?? null);
        $record['Presentation Experience'] = $this->stringValue($row['presentation_experience'] ?? null);
        $record['Presentation Topics'] = $this->stringValue($row['presentation_topics'] ?? null);
        $record['Preferred Schedule'] = $this->stringValue($row['preferred_schedule'] ?? null);
        $record['Suggestions'] = $this->stringValue($row['suggestions'] ?? null);
        $record['Code of Conduct Agreement'] = $this->agreementValue($row['code_of_conduct_agreed'] ?? null);
        $record['Recording Agreement'] = $this->agreementValue($row['recording_agreed'] ?? null);
        $record['Parent Permission'] = $this->agreementValue($row['parent_permission_granted'] ?? null);

        return $record;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return $value === true ? '1' : '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        return trim((string) $value);
    }

    private function dateValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return $this->stringValue($value);
    }

    private function agreementValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    private function usableEmail(mixed $value): string
    {
        $email = strtolower($this->stringValue($value));
        if ($email === '' || str_ends_with($email, '.invalid')) {
            return '';
        }
        return $email;
    }
}
