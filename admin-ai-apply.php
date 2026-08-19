<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];
if ($studentId === '' || $selection === [] || $research === []) {
    redirect_to('admin.php?status=ai-missing#ai-mentor-reviews');
}
try {
    $repository = ai_review_repository();
    if (!$repository instanceof \YuvaClub\AI\SqlAiReviewRepository) {
        throw new RuntimeException('SQL AI review persistence is unavailable.');
    }
    $adminUserId = find_sql_admin_user_id((string) $admin['email']);
    if ($adminUserId === null) {
        throw new RuntimeException('SQL admin identity is unavailable.');
    }
    try {
        $document = research_document_for_student($studentId, $research);
    } catch (\YuvaClub\Submission\DocumentResolutionException) {
        $repository->markLatestStale($studentId, 'Research Document Changed');
        redirect_to('admin.php?status=ai-stale#ai-mentor-reviews');
    }
    $result = $repository->apply(
        $studentId,
        \YuvaClub\AI\AiMentorService::sourceRevisionHash($selection, $research, $document),
        $adminUserId
    );
    redirect_to(match ($result) {
        'stale' => 'admin.php?status=ai-stale#ai-mentor-reviews',
        'missing' => 'admin.php?status=ai-missing#ai-mentor-reviews',
        'already-applied' => 'admin.php?status=ai-already-applied#ai-mentor-reviews',
        default => 'admin.php?status=ai-applied#ai-mentor-reviews',
    });
} catch (Throwable $error) {
    error_log('YUVA AI apply failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    redirect_to('admin.php?status=ai-error#ai-mentor-reviews');
}
