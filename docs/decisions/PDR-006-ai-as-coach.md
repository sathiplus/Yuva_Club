# PDR-006 - AI as Coach

## Date

July 10, 2026

## Status

Accepted

## Volume / Chapter Affected

AI, Student Platform, Presentation Center, Practice Mode, Mentor Platform, Master Administration, Security.

## Decision

AI is a coach, not a decision maker. AI may provide feedback, recommendations, summaries, and practice guidance, but it must not be the final judge of a student's ability, value, eligibility, or long-term record.

## Reason

Students need encouraging and explainable feedback. AI can help scale coaching, but minors and learning outcomes require transparency, human review, and careful guardrails.

## Alternatives Considered

- Use AI scores as final official evaluations.
- Hide AI limitations from students and parents.
- Allow AI feedback to directly control certificates without review.

## Consequences

- AI outputs must be labeled as AI-generated.
- AI prompts, rubrics, and model policies must be versioned.
- Sensitive or high-impact AI results must support human review.
- AI feedback should be framed as improvement guidance.
- AI usage must respect consent, visibility, and data governance rules.

## Developer Impact

- Never implement AI as a black-box final authority.
- Never store unversioned AI prompts.
- Never expose AI feedback to unauthorized users.
- Build review workflows for flagged AI outputs.

## Follow-Up Work

- Define AI Standards expansion.
- Define AI Coach chapter.
- Define AI review queue in Master Administration.
- Define AI audit events.

