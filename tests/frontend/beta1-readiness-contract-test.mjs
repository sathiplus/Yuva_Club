import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
const root=new URL('../../',import.meta.url);
const [portal,parent,growth,repo,registration,submission,login,metrics,runbook]=await Promise.all([
  'portal.php','parent.php','my-growth.php','backend/repositories.php','registration.php','submit-registration.php','portal-login.php','tools/beta1-metrics-report.sql','docs/beta1/readiness-runbook.md'
].map(file=>readFile(new URL(file,root),'utf8')));
const mission=portal.indexOf('Today’s Challenge'), growthHome=portal.indexOf('home-growth-card'), mentor=portal.indexOf('home-mentor-card'), leadership=portal.indexOf('home-progress-card');
assert.ok(mission>0&&mission<growthHome&&growthHome<mentor&&mentor<leadership,'Student Home priority order must be Mission, Growth, AI Mentor, Leadership.');
assert.ok(!portal.includes('Streak</span><strong>Not tracked yet'),'Unfinished Streak UI must be absent.');
for(const copy of ['AI coaching score for practice. It is not an official competition score.','Your highest score on comparable practice challenges.','A private target set by YUVA—not another student’s score.','Premium gives access to additional AI Mentor coaching and advanced features.'])assert.ok(portal.includes(copy)||growth.includes(copy),`Missing Beta help text: ${copy}`);
assert.ok(portal.includes('AI Practice Score')&&parent.includes('Official Rubric Score')&&parent.includes('not an official competition result'),'Practice and official results must be visibly distinct.');
for(const event of ['beta.registration_started','beta.registration_completed','beta.first_login','beta.my_growth_viewed','beta.ai_mentor_used'])assert.ok(repo.includes(event)||registration.includes(event)||submission.includes(event)||login.includes(event)||portal.includes(event)||growth.includes(event),`Missing ${event}`);
assert.ok(repo.includes("'ip_address' =>")&&repo.includes('function log_beta_event')&&repo.includes('NULL, NULL'),'Beta events must omit IP and user-agent.');
for(const metric of ['registration_completion_pct','first_login_pct','first_challenge_started_pct','first_challenge_completed_pct','first_ai_score_pct','repeat_attempt_pct','improvement_after_repeat_pct','personal_best_pct','benchmark_beat_pct','week2_return_pct','ai_mentor_usage_pct','my_growth_usage_pct'])assert.ok(metrics.includes(metric),`Missing metric ${metric}`);
for(const source of ['quick_challenge_attempts','quick_challenge_evaluations','student_challenge_personal_bests'])assert.ok(metrics.includes(source),`Metrics must derive from authoritative ${source}.`);
assert.ok(runbook.includes('Production email gate')&&runbook.includes('One fresh-family production rehearsal')&&runbook.includes('Feedback forms'),'Readiness runbook is incomplete.');
console.log('PASS Beta 1 readiness UX, telemetry, metrics, and runbook contracts');
