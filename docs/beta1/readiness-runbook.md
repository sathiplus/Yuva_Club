# YUVA Beta 1 readiness runbook

## Measurement sources

Only events absent from the authoritative product model are added to `activity_logs`: `beta.registration_started`, `beta.registration_completed`, `beta.first_login`, `beta.ai_mentor_used`, and `beta.my_growth_viewed`. Challenge milestones are derived instead of duplicated: `first_challenge_started` and `second_challenge_attempt` come from `quick_challenge_attempts`; `first_challenge_completed` and `first_ai_score_received` come from completed `quick_challenge_evaluations`; Personal Best and benchmark outcomes come from `student_challenge_personal_bests`. The report never stores passwords, tokens, email addresses, response bodies, IP addresses, or user-agent strings.

## Campaign configuration

- Name/code: YUVA Beta 1 / `YUVA-BETA-1`
- Capacity: 12 students; 4 Junior, 4 Senior, 4 mixed experience/background
- Premium duration: 30 days
- Access: one unique, student-bound, single-use invitation per student; never a shared code
- Open invitations only after the production email gate passes. Do not enable billing.

## Production email gate

Use one controlled deliverable Student mailbox and one controlled deliverable Parent mailbox. For Parent activation, Student setup/reset, Beta invitation, and registration/approval notification: trigger the supported production route once; confirm receipt, sender, understandable subject, mobile layout, and a `https://www.yuvaclub.app/` link. Confirm expiry and single use without copying tokens into notes, logs, screenshots, or reports. Verify neutral responses and that tokens remain hashed at rest. Clean only the controlled records after evidence is recorded.

## AI quality sample

Run five bounded samples: Junior typed research and short presentation; Senior typed research, presentation, and Quick Challenge. Score reading level, length, tone, usefulness, actionability, sensitive-trait safety, and consistency. Quick Challenge must retain **What You Did Well / Try Next / Practice Mission**. If output is long, first shorten the presentation layer (one primary action plus collapsed detail); do not broadly rewrite prompts without separate evidence and authorization.

## One fresh-family production rehearsal

Use exactly one new controlled Student and one deliverable Parent. Run registration, Master Admin approval, Student Email + Password login, Handle/Avatar, one unique Beta invitation, Premium activation, Quick Challenge, one authorized AI Practice Score, Personal Best, Benchmark, My Growth, AI Mentor, and Leadership Journey. Then run Parent activation, password creation/login, My Children, linked-child Growth/challenge result, and one consent/approval path. Verify Master Admin status and entitlement throughout. Decide before cleanup whether this remains the named internal test family or is removed using the established dependency-first cleanup; never partially delete immutable Leadership history.

## Feedback forms

### Student — after first AI score

1. Was it clear what to do? (Yes / Mostly / No)
2. Was the feedback useful? (Yes / Somewhat / No)
3. Did it make you want to try again? (Yes / Maybe / No)
4. What was confusing? (optional)

### Student — week two

1. What did you enjoy most?
2. What helped you improve?
3. What was confusing?
4. What would make you come back?

### Parent

1. Can you understand your child's progress? (Yes / Mostly / No)
2. Is My Growth useful? (Yes / Somewhat / No)
3. Is AI Mentor valuable? (Yes / Unsure / No)
4. Would this be worth paying for later? (Yes / Maybe / No)
5. Optional comments

Collect only a random Beta participant code with these answers—no child name, email, response text, or AI content.
