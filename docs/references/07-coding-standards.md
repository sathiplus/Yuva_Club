# Coding Standards

## Purpose

This document defines coding expectations for YUVA Club implementation work.

## General Standards

- Keep business rules in backend services, not scattered across UI pages.
- Keep configuration in database or environment settings, not hard-coded constants.
- Validate on both frontend and backend.
- Prefer clear names over clever names.
- Keep functions small enough to test.
- Log meaningful operational events without leaking sensitive data.

## Configuration Over Code

Never hard-code administrator-managed values:

- Countries
- States and provinces
- Cities
- Schools
- Organization types
- Leadership levels
- Grade groups
- Age groups
- Certificate templates
- Badge definitions
- Presentation topics
- Rubrics
- Subscription plans
- Notification templates
- AI prompt versions

## Error Handling

- Return standard API error codes.
- Show user-friendly frontend messages.
- Log technical details server-side.
- Do not leak secrets, tokens, stack traces, or database details to users.

## Developer Notes

- Never generate YUVA IDs on the frontend.
- Never bypass audit logging for sensitive actions.
- Never duplicate validation rules without documenting the source of truth.
- Never add a feature without an acceptance test plan.

