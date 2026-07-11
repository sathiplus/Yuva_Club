# API Standards

## Purpose

The YUVA API Standards define how endpoints are designed, documented, secured, versioned, validated, and tested.

## Base Path

All public application APIs should use versioned paths:

```http
/api/v1
```

## Endpoint Documentation Requirement

Every endpoint must define:

- Purpose
- Method and path
- Authentication
- Authorization
- Request schema
- Response schema
- Validation rules
- Error codes
- Audit events
- Rate limits
- Idempotency behavior where applicable

## Naming Rules

- Use nouns for resources.
- Use plural resource names.
- Use action subroutes only when the action is not a normal CRUD operation.

Examples:

```http
POST /api/v1/auth/register
POST /api/v1/auth/verify-email
GET /api/v1/students/me
PUT /api/v1/students/me/profile
POST /api/v1/organizations/join
POST /api/v1/consent/request
```

## Standard Response Envelope

Successful response:

```json
{
  "data": {},
  "meta": {
    "requestId": "req_123"
  }
}
```

Error response:

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "One or more fields are invalid.",
    "details": []
  },
  "meta": {
    "requestId": "req_123"
  }
}
```

## Standard Error Codes

| Code | Meaning |
|---|---|
| VALIDATION_FAILED | Request failed validation |
| UNAUTHENTICATED | User is not signed in |
| UNAUTHORIZED | User lacks permission |
| NOT_FOUND | Resource does not exist or is not visible |
| CONFLICT | Resource state conflicts with request |
| RATE_LIMITED | Too many requests |
| TOKEN_EXPIRED | Token is expired |
| INVITATION_INVALID | Invitation cannot be used |
| JOIN_CODE_INVALID | Join code cannot be used |
| CONSENT_REQUIRED | Parent or guardian consent is required |
| INTERNAL_ERROR | Unexpected server error |

## Security Requirements

- No sensitive data in URLs.
- Validate every request server-side.
- Enforce authorization in backend, not only frontend.
- Include audit events for sensitive operations.
- Rate-limit public endpoints.
- Use idempotency keys for retryable writes where duplicate effects are harmful.

## Developer Notes

- Never expose raw database IDs when a public opaque ID is safer.
- Never rely on frontend validation alone.
- Never return different error details that allow account enumeration during login or password reset.
- Never introduce an unversioned API.

