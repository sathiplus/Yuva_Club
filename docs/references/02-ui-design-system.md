# YUVA UI Design System

## Purpose

The YUVA UI Design System defines reusable interface rules and components so every portal feels consistent, accessible, and maintainable.

This document should be treated like YUVA's internal Material Design or Fluent-style reference.

## UX Philosophy

Students should reach their next meaningful action in three clicks or fewer.

The interface should be calm, clear, encouraging, and structured. It should support young users without feeling childish, and it should support parents, mentors, organization admins, and master admins without visual confusion.

## Design Tokens

### Color Roles

| Token | Purpose |
|---|---|
| `color-primary` | Primary actions, links, active navigation |
| `color-primary-hover` | Primary hover/focus state |
| `color-surface` | Page background |
| `color-card` | Card and panel background |
| `color-border` | Borders and separators |
| `color-text` | Main text |
| `color-muted` | Secondary text |
| `color-success` | Success states |
| `color-warning` | Warning states |
| `color-danger` | Error and destructive states |
| `color-ai` | AI coach indicators and feedback blocks |

### Typography

| Role | Requirement |
|---|---|
| Page title | One per page |
| Section heading | Clear grouping inside page |
| Body text | Readable at desktop and mobile sizes |
| Form label | Always visible; do not rely only on placeholders |
| Helper text | Short and specific |
| Error text | Plain-language correction |

### Spacing

Use consistent spacing tokens instead of ad hoc margins:

- `space-1`
- `space-2`
- `space-3`
- `space-4`
- `space-6`
- `space-8`

## Core Components

### Button

Variants:

- Primary
- Secondary
- Danger
- Ghost
- Icon
- Loading
- Disabled

Requirements:

- Every button has a clear action label or accessible name.
- Loading buttons prevent duplicate submission.
- Danger buttons require confirmation for destructive actions.
- Icon-only buttons require tooltip and accessible label.

### Form Field

Requirements:

- Visible label
- Helper text where useful
- Inline validation
- Error message linked to field
- Accessible focus state
- Mobile-friendly input type

### Card

Use cards for repeated items or contained summaries, not for every section.

Card types:

- Achievement card
- Presentation card
- Event card
- Certificate card
- Student summary card
- Organization summary card
- AI feedback card

### Table

Tables are for structured comparison and data management, especially admin areas.

Requirements:

- Sort when useful
- Filter when useful
- Search for large datasets
- Empty state
- Loading state
- Pagination or virtual scrolling when needed
- Mobile alternative layout for narrow screens

### AI Widget

AI widgets must clearly identify AI-generated content.

Requirements:

- Explain the feedback source
- Provide confidence or limitation note where appropriate
- Allow review or feedback when outputs affect records
- Never visually present AI as a final judge

### Notification

Types:

- Success
- Info
- Warning
- Error
- AI suggestion

## Accessibility Rules

- Keyboard navigation is required.
- Visible focus state is required.
- Color cannot be the only indicator.
- Forms must be screen-reader friendly.
- Error messages must be specific.
- Touch targets must be large enough for mobile users.

## Developer Notes

- Never create one-off button styles in feature pages.
- Never hard-code color values directly in components.
- Never hide labels behind placeholder-only fields.
- Never use AI styling for non-AI content.
- Never rely on hover-only interactions for core student workflows.

