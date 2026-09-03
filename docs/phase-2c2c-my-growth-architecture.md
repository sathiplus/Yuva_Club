# Phase 2C.2C — My Growth architecture

## Reused authoritative data

- Quick Challenge evaluations provide completed AI Practice Scores, provenance,
  benchmark targets, compatible scoring-policy/rubric versions, and recent activity.
- Quick Challenge template-version skills provide the ten-skill catalog mapping.
- `student_challenge_personal_bests` remains the only Personal Best authority.
- Presentation verifications, leadership reflections, and approved Leadership
  levels contribute verified accomplishment and weekly consistency.
- Existing AI Mentor reviews remain the authority for AI coaching activity; My
  Growth never invokes AI during rendering.

## New persistence

Migration 20 adds only versioned achievement definitions, deduplicated earned
achievements, and corrective audit records. It adds no score, benchmark, trend,
activity, or Leadership history table.

## Compatibility and performance

Skill change is compared only inside the same template family, scoring-policy
version, and rubric version. The service bounds evaluations to the latest 50,
trend points to five, Personal Bests to 20, activity to eight, and uses indexed
student/status/time access paths. One service call composes the page; no AI call
occurs.

## Healthy consistency

One completed Quick Challenge evaluation, verified presentation, or meaningful
leadership reflection qualifies an ISO week. Consecutive weeks are counted back
from the current week. Logins, elapsed screen time, refreshes, and incomplete
attempts never qualify.

## Impromptu mode

Already supported. Phase 2C.2A has `challenge_type=impromptu`, server-side
preparation/response deadlines, `prompt_reveal_mode=on_start`, immutable response
snapshots, AI scoring, Personal Best, and benchmark support. Phase 2C.2C does not
create a second engine or seed a production template.

## Beta metrics

The existing attempt, evaluation, Personal Best, and completion timestamps can
answer attempted, repeated, improved, Personal Best, benchmark, and another-week
return metrics using grouped, privacy-preserving SQL. These should remain
aggregate operational queries; no student growth profile is public.
