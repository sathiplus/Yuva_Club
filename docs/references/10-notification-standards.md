# Notification Standards

## Purpose

This document defines standards for YUVA Club email, in-app, SMS if enabled, and future push notifications.

## Notification Philosophy

Notifications should help students and families act, not create noise.

## Channels

- Email
- In-app
- SMS, if enabled
- Push, future

## Required Notification Fields

| Field | Requirement |
|---|---|
| Template ID | Stable identifier |
| Audience | Student, parent, mentor, organization admin, master admin |
| Channel | Email, in-app, SMS, push |
| Trigger | Event that sends notification |
| Required Variables | Data used in template |
| Preference Rules | Whether user can opt out |
| Audit Requirement | Whether sending must be audited |

## Registration Notifications

Required notifications:

- Welcome email
- Email verification
- Parent consent request
- Parent consent approved
- Organization invitation accepted
- Organization join pending approval
- Organization join approved
- Password reset
- Account recovery update

## Template Rules

- Use plain language.
- Include the student's first or preferred name where appropriate.
- Avoid sensitive details in subject lines.
- Include support path for account or consent issues.
- Respect notification preferences unless the message is legally or operationally required.

## Developer Notes

- Never hard-code notification copy inside feature logic.
- Never send minor-sensitive details to unverified contacts.
- Never send duplicate messages on retry without idempotency.
- Never ignore delivery failures for registration and consent workflows.

