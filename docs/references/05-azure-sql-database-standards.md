# Azure SQL Database Standards

## Purpose

This document defines database modeling standards for YUVA Club.

## Database Philosophy

Normalize first. Denormalize only for measured performance needs, analytics materialization, or clearly justified reporting use cases.

## Required Table Conventions

Every core table should include:

- Stable primary key
- Created timestamp
- Updated timestamp where mutable
- Created by where relevant
- Updated by where relevant
- Status field where lifecycle matters
- Audit or history table for sensitive mutable records

## Naming

Use clear singular or plural naming consistently within the project. Avoid abbreviations that future developers will not understand.

Preferred examples:

- Users
- Students
- Parents
- Organizations
- StudentOrganizationMemberships
- ConsentRecords
- AuditLogs
- NotificationTemplates

## Keys and IDs

- Use internal primary keys for relational integrity.
- Use public opaque IDs where external exposure is needed.
- YUVA ID is a permanent student identity and must be unique, immutable, and never reused.

## Index Standards

Create indexes for:

- Unique email lookup
- YUVA ID lookup
- Invitation token lookup
- Join code lookup
- Membership by student
- Membership by organization
- Consent by student and status
- Audit logs by actor/date and target/date

## History and Audit

Use history tables or immutable records for:

- Consent
- Role assignments
- Organization membership changes
- YUVA ID generation
- Sensitive admin actions
- AI policy changes
- Certificate revocation

## Developer Notes

- Never make organization membership the source of student identity.
- Never delete consent history.
- Never reuse YUVA IDs.
- Never hard-code configurable values that belong in reference tables.
- Never add analytics-only denormalization without documenting the source of truth.

