<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';

$student = require_student();
$yuvaId = normalize_yuva_id((string)($student['Yuva Club ID'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403); exit('Invalid request.');
}
$lastAttempt=(int)($_SESSION['public_identity_update_at']??0);
if($lastAttempt>0&&time()-$lastAttempt<3){http_response_code(429);exit('Please wait before trying again.');}
$_SESSION['public_identity_update_at']=time();
try {
    public_identity_service()->updateOwn(
        $yuvaId,
        normalize_yuva_id((string)($_POST['yuva_id']??'')),
        (string)($_POST['public_handle']??''),
        (string)($_POST['avatar_code']??'')
    );
    audit_log_event($yuvaId,YUVA_ROLE_STUDENT,student_organization_id($student),'student.public_identity.update','student',$yuvaId,true);
    redirect_to('portal.php?status=identity-saved#app-profile');
} catch (Throwable $error) {
    $safeMessages=[
        \YuvaClub\Identity\PublicIdentityValidator::GENERIC_ERROR,
        'Your YUVA Handle can be changed once every 30 days.',
        'Please choose an available YUVA avatar.',
        'Access denied.',
    ];
    $message=$error->getMessage();
    $safe=str_starts_with($message,\YuvaClub\Identity\PublicIdentityValidator::GENERIC_ERROR)||in_array($message,$safeMessages,true)
        ?$message:'Your YUVA Identity could not be saved. Please try again.';
    $_SESSION['public_identity_error']=$safe;
    audit_log_event($yuvaId,YUVA_ROLE_STUDENT,student_organization_id($student),'student.public_identity.update','student',$yuvaId,false,['reason'=>'validation']);
    redirect_to('portal.php?status=identity-error#app-profile');
}
