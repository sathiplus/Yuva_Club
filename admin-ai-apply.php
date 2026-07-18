<?php
require __DIR__ . '/portal-lib.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('admin.php?status=security-error');
}

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
if ($studentId === '') {
    redirect_to('admin.php?status=ai-missing');
}

$result = with_ai_apply_lock(function () use ($studentId): string {
    $reviews = ai_reviews();
    $reviewRecord = $reviews[$studentId] ?? [];
    $draft = $reviewRecord['review'] ?? [];
    if (!is_array($reviewRecord) || !is_array($draft) || $draft === []) {
        return 'missing';
    }

    $status = (string) ($reviewRecord['status'] ?? '');
    if (str_starts_with($status, 'Stale - ')) {
        return 'stale';
    }

    $reviewId = ai_review_identifier($studentId, $reviewRecord);
    if ($reviewId === '') {
        return 'missing';
    }

    $records = read_json_file(portal_records_file());
    $record = $records[$studentId] ?? student_record($studentId);
    $alreadyApplied = ($record['last_applied_ai_review_id'] ?? '') === $reviewId
        || $status === 'Applied by Admin';
    $appliedAt = (string) ($reviewRecord['applied_at'] ?? date('Y-m-d H:i:s'));

    if ($alreadyApplied) {
        $records[$studentId] = array_merge($record, [
            'last_applied_ai_review_id' => $reviewId,
            'ai_review_applied_at' => $appliedAt,
        ]);
        write_json_file(portal_records_file(), $records);
        $reviews[$studentId]['review_id'] = $reviewId;
        $reviews[$studentId]['applied_review_id'] = $reviewId;
        $reviews[$studentId]['applied_at'] = $appliedAt;
        $reviews[$studentId]['status'] = 'Applied by Admin';
        write_json_file(ai_reviews_file(), $reviews);
        return 'already-applied';
    }

    $points = max(0, min(100, (int) ($draft['total_points'] ?? 0)));
    $tokens = max(0, (int) ($record['tokens'] ?? 0))
        + max(0, min(4, (int) ($draft['suggested_tokens'] ?? intdiv($points, 25))));
    $appliedAt = date('Y-m-d H:i:s');

    $records[$studentId] = array_merge($record, [
        'points' => (string) $points,
        'tokens' => (string) $tokens,
        'score' => (string) $points,
        'rank_recommendation' => rank_eligibility(array_merge($record, ['points' => (string) $points])),
        'ai_feedback_summary' => clean_text((string) ($draft['summary'] ?? '')),
        'communication_skills' => clean_text((string) ($draft['communication_skills'] ?? '')),
        'leadership_milestones' => clean_text((string) ($draft['leadership_milestones'] ?? '')),
        'teacher_feedback' => clean_text((string) ($draft['summary'] ?? ($record['teacher_feedback'] ?? ''))),
        'last_applied_ai_review_id' => $reviewId,
        'ai_review_applied_at' => $appliedAt,
        'updated_at' => $appliedAt,
    ]);
    write_json_file(portal_records_file(), $records);

    $reviews[$studentId]['review_id'] = $reviewId;
    $reviews[$studentId]['status'] = 'Applied by Admin';
    $reviews[$studentId]['applied_review_id'] = $reviewId;
    $reviews[$studentId]['applied_at'] = $appliedAt;
    write_json_file(ai_reviews_file(), $reviews);

    return 'applied';
});

redirect_to(match ($result) {
    'stale' => 'admin.php?status=ai-stale',
    'missing' => 'admin.php?status=ai-missing',
    'already-applied' => 'admin.php?status=ai-already-applied',
    default => 'admin.php?status=ai-applied',
});
