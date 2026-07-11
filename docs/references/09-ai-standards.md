# AI Standards

## Purpose

This document defines how AI should be designed, implemented, governed, and reviewed in YUVA Club.

## AI Philosophy

AI is a coach, never a judge.

AI should help students understand strengths, practice communication, build confidence, and choose next actions. It must not permanently label a student's ability, character, or potential.

## Required AI Documentation

Every AI feature must define:

- Purpose
- Inputs
- Outputs
- Prompt version
- Model or provider
- Safety rules
- Human review path
- Data retention
- Audit events
- User-facing explanation
- Failure behavior

## Student AI Rules

- Feedback must be encouraging and specific.
- AI should explain limitations.
- AI outputs affecting official records must be reviewable.
- AI must respect consent and visibility rules.
- AI prompts and rubrics must be versioned.

## Onboarding AI

AI onboarding may ask about:

- Learning goals
- Confidence level
- Presentation experience
- Favorite subjects
- Leadership interests
- Communication challenges
- Learning preferences

AI onboarding may produce:

- Initial learning plan
- Confidence baseline
- Suggested first topic
- Suggested first practice activity
- Recommended next dashboard action

## Developer Notes

- Never use AI as the sole authority for high-stakes decisions.
- Never store unversioned prompts.
- Never expose AI feedback to unauthorized users.
- Never hide that feedback is AI-generated.

