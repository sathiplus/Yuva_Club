<?php
declare(strict_types=1);
function scheck(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__,2);$migration=(string)file_get_contents($root.'/database/15-subscription-entitlement-foundation-phase3a1.azure-sql.sql');$rollback=(string)file_get_contents($root.'/database/15-subscription-entitlement-foundation-phase3a1-rollback.sql');$service=(string)file_get_contents($root.'/backend/subscription-entitlement.php');
foreach(['subscription_plans','plan_feature_versions','plan_feature_rules','student_entitlements','promo_campaigns','promo_invitations','promo_campaign_participants','subscription_access_restrictions','subscription_audit'] as $table)scheck(str_contains($migration,"dbo.$table"),'Migration 15 missing '.$table);
foreach(['plan_code','feature_key','entitlement_guid','token_hash BINARY(32)','expires_at','used_at','revoked_at','row_version ROWVERSION'] as $contract)scheck(str_contains($migration,$contract),'Schema contract missing '.$contract);
scheck(str_contains($migration,"plan_code=N'free'")&&str_contains($migration,"plan_code=N'premium'"),'Free/Premium seed missing.');
scheck(str_contains($migration,'OBJECT_ID')&&str_contains($migration,'sys.indexes'),'Migration must be idempotent.');
scheck(!str_contains($migration,'dbo.organizations'),'Phase 3A.1 must not depend on skipped Migration 05.');
scheck(str_contains($rollback,"DB_NAME() NOT LIKE N'%rehearsal%'"),'Rollback must be rehearsal-only.');
scheck(str_contains($service,"hash('sha256',\$raw)")&&str_contains($service,'CONVERT(binary(32),:hash,2)'),'Token boundary must persist only a SHA-256 digest.');
scheck(!str_contains($migration,'raw_token')&&!str_contains($migration,'invitation_code'),'Raw token must not be persisted.');
scheck(str_contains($service,"used_at IS NULL")&&str_contains($service,"expires_at>SYSUTCDATETIME()")&&str_contains($service,"revoked_at IS NULL"),'Single-use expiry/revocation checks missing.');
scheck(str_contains($service,"['yuva_id'=>\$student['yuva_id'],'plan_code'=>'free'")&&str_contains($service,'hasFeature'),'Central resolver or Free fallback missing.');
scheck(str_contains($service,"!==YUVA_ROLE_MASTER_ADMIN"),'Master Admin boundary missing.');
scheck(str_contains($service,'intended_student_id=:student'),'Redemption is not student-bound.');
scheck(str_contains($service,'promo_redemption_block'),'Revoke-and-block contract missing.');
scheck(!str_contains($service,'AI_MENTOR_PREMIUM_ENTITLEMENT_ENABLED'),'Foundation must not gate AI Mentor.');
echo "Subscription entitlement Phase 3A.1 contracts PASS\n";
