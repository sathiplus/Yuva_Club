-- Read-only Beta 1 metrics. Set the campaign window before running.
DECLARE @campaign_code NVARCHAR(40)=N'YUVA-BETA-1';
DECLARE @starts_at DATETIME2='2026-09-01T00:00:00';
DECLARE @ends_at DATETIME2=DATEADD(day,30,@starts_at);

WITH beta_students AS (
  SELECT DISTINCT e.student_id
  FROM dbo.student_entitlements e
  WHERE e.source_type=N'PREMIUM_BETA_PROMO' AND e.source_reference=@campaign_code
), attempts AS (
  SELECT a.student_id,a.[status],a.started_at,a.submitted_at,
         ROW_NUMBER() OVER(PARTITION BY a.student_id ORDER BY a.started_at,a.id) student_attempt_number
  FROM dbo.quick_challenge_attempts a JOIN beta_students b ON b.student_id=a.student_id
  WHERE a.started_at>=@starts_at AND a.started_at<@ends_at
), attempt_totals AS (
  SELECT student_id,COUNT(*) attempt_count,
         MAX(CASE WHEN [status]=N'Submitted' THEN 1 ELSE 0 END) completed,
         COUNT(DISTINCT CONCAT(YEAR(started_at),N'-',DATEPART(isowk,started_at))) active_weeks
  FROM attempts GROUP BY student_id
), scored AS (
  SELECT e.student_id,e.template_version_id,e.total_score,e.benchmark_score,e.completed_at,
         ROW_NUMBER() OVER(PARTITION BY e.student_id,e.template_version_id ORDER BY e.completed_at,e.id) attempt_number,
         LAG(e.total_score) OVER(PARTITION BY e.student_id,e.template_version_id ORDER BY e.completed_at,e.id) prior_score
  FROM dbo.quick_challenge_evaluations e JOIN beta_students b ON b.student_id=e.student_id
  WHERE e.[status]=N'Completed' AND e.completed_at>=@starts_at AND e.completed_at<@ends_at
), per_student AS (
  SELECT student_id,COUNT(*) scored_attempts,
         MAX(CASE WHEN prior_score IS NOT NULL AND total_score>prior_score THEN 1 ELSE 0 END) improved,
         MAX(CASE WHEN total_score>=benchmark_score THEN 1 ELSE 0 END) beat_benchmark
  FROM scored GROUP BY student_id
), totals AS (
  SELECT
    (SELECT COUNT(*) FROM dbo.activity_logs WHERE action=N'beta.registration_started' AND created_at>=@starts_at AND created_at<@ends_at) registration_started,
    (SELECT COUNT(*) FROM dbo.activity_logs WHERE action=N'beta.registration_completed' AND created_at>=@starts_at AND created_at<@ends_at) registration_completed,
    (SELECT COUNT(*) FROM beta_students) activated_students,
    (SELECT COUNT(DISTINCT l.actor_user_id) FROM dbo.activity_logs l JOIN dbo.students s ON s.user_id=l.actor_user_id JOIN beta_students b ON b.student_id=s.id WHERE l.action=N'beta.first_login' AND l.created_at>=@starts_at AND l.created_at<@ends_at) first_login_students,
    (SELECT COUNT(*) FROM attempt_totals WHERE attempt_count>=1) challenge_started_students,
    (SELECT COUNT(*) FROM attempt_totals WHERE completed=1) challenge_completed_students,
    (SELECT COUNT(*) FROM per_student WHERE scored_attempts>=1) scored_students,
    (SELECT COUNT(*) FROM attempt_totals WHERE attempt_count>=2) repeated_students,
    (SELECT COUNT(*) FROM per_student WHERE scored_attempts>=2 AND improved=1) improved_students,
    (SELECT COUNT(DISTINCT pb.student_id) FROM dbo.student_challenge_personal_bests pb JOIN beta_students b ON b.student_id=pb.student_id WHERE pb.achieved_at>=@starts_at AND pb.achieved_at<@ends_at) personal_best_students,
    (SELECT COUNT(*) FROM per_student WHERE beat_benchmark=1) benchmark_students,
    (SELECT COUNT(*) FROM attempt_totals WHERE active_weeks>=2) week2_students,
    (SELECT COUNT(DISTINCT l.actor_user_id) FROM dbo.activity_logs l JOIN dbo.students s ON s.user_id=l.actor_user_id JOIN beta_students b ON b.student_id=s.id WHERE l.action=N'beta.ai_mentor_used' AND l.created_at>=@starts_at AND l.created_at<@ends_at) ai_mentor_students,
    (SELECT COUNT(DISTINCT l.actor_user_id) FROM dbo.activity_logs l JOIN dbo.students s ON s.user_id=l.actor_user_id JOIN beta_students b ON b.student_id=s.id WHERE l.action=N'beta.my_growth_viewed' AND l.created_at>=@starts_at AND l.created_at<@ends_at) my_growth_students
)
SELECT *,
  CAST(100.0*registration_completed/NULLIF(registration_started,0) AS DECIMAL(5,1)) registration_completion_pct,
  CAST(100.0*first_login_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) first_login_pct,
  CAST(100.0*challenge_started_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) first_challenge_started_pct,
  CAST(100.0*challenge_completed_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) first_challenge_completed_pct,
  CAST(100.0*scored_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) first_ai_score_pct,
  CAST(100.0*repeated_students/NULLIF(challenge_started_students,0) AS DECIMAL(5,1)) repeat_attempt_pct,
  CAST(100.0*improved_students/NULLIF(repeated_students,0) AS DECIMAL(5,1)) improvement_after_repeat_pct,
  CAST(100.0*personal_best_students/NULLIF(scored_students,0) AS DECIMAL(5,1)) personal_best_pct,
  CAST(100.0*benchmark_students/NULLIF(scored_students,0) AS DECIMAL(5,1)) benchmark_beat_pct,
  CAST(100.0*week2_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) week2_return_pct,
  CAST(100.0*ai_mentor_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) ai_mentor_usage_pct,
  CAST(100.0*my_growth_students/NULLIF(activated_students,0) AS DECIMAL(5,1)) my_growth_usage_pct
FROM totals;
