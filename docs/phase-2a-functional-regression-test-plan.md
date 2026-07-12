# YUVA Club Phase 2A Functional and Regression Test Plan

Date: 2026-07-12

Status: Created for staging execution. Not executed locally because no PHP runtime or staging test accounts are available in this shell.

## Parent Access Tests

| ID | Test | Expected Result | Status |
| --- | --- | --- | --- |
| PARENT-001 | Correct parent email and password can authenticate. | Parent reaches dashboard. | Not executed |
| PARENT-002 | Incorrect parent password is rejected. | Generic login failure. | Not executed |
| PARENT-003 | Parent opens an unlinked student ID. | Access denied and audit event written. | Not executed |
| PARENT-004 | Parent changes `id` in URL to another student. | Access denied and audit event written. | Not executed |
| PARENT-005 | Parent linked to multiple students switches between children. | Only linked children are available. | Not executed |
| PARENT-006 | Expired parent session opens dashboard. | Redirect to parent login. | Not executed |
| PARENT-007 | Parent logout invalidates dashboard access. | Dashboard redirects/requires login. | Not executed |
| PARENT-008 | Repeated failed attempts trigger rate limit. | Temporary lockout. | Not executed |
| PARENT-009 | Failed parent login/access creates audit event. | JSONL audit event exists. | Not executed |

## Admin Access Tests

| ID | Test | Expected Result | Status |
| --- | --- | --- | --- |
| ADMIN-001 | Master Admin signs in. | Admin dashboard opens. | Not executed |
| ADMIN-002 | Organization Admin tries MasterAdmin route. | Access denied. | Not executed |
| ADMIN-003 | Student session tries admin route. | Redirect or access denied. | Not executed |
| ADMIN-004 | Parent session tries admin route. | Redirect or access denied. | Not executed |
| ADMIN-005 | Admin POST without CSRF. | Rejected. | Not executed |
| ADMIN-006 | Admin POST with invalid CSRF. | Rejected. | Not executed |
| ADMIN-007 | Authorized admin POST with valid CSRF. | Succeeds and logs audit event. | Not executed |
| ADMIN-008 | Sensitive admin action creates audit event. | JSONL audit event exists. | Not executed |

## Organization Isolation Tests

| ID | Test | Expected Result | Status |
| --- | --- | --- | --- |
| ORG-001 | Organization A reads Organization B student. | Rejected by server-side organization context. | Blocked: org admin access intentionally disabled in Phase 2A |
| ORG-002 | Organization A updates Organization B student. | Rejected by server-side organization context. | Blocked: org admin access intentionally disabled in Phase 2A |
| ORG-003 | Organization A deletes Organization B data. | Rejected by server-side organization context. | Blocked: org admin access intentionally disabled in Phase 2A |
| ORG-004 | Browser-supplied OrganizationId attempts override. | Server ignores browser value. | Blocked: org admin access intentionally disabled in Phase 2A |

## Regression Tests

| Flow | Expected Result | Status |
| --- | --- | --- |
| Student registration | Student registration completes. | Not executed |
| Student login | Student can sign in with supported identifier and password. | Not executed |
| Student dashboard | Student dashboard loads. | Not executed |
| Parent account activation | Secure setup/activation exists. | Failing gap: not implemented for existing parents |
| Parent login | Parent can sign in after secure account creation. | Not executed |
| Parent dashboard | Parent sees only linked students. | Not executed |
| Master Admin login | Master Admin can sign in. | Not executed |
| Organization Admin login | Org admin is not publicly enabled. | Blocked by design in Phase 2A |
| Logout | Session fields clear. | Not executed |
| Password reset | Reset flow works for eligible accounts. | Not executed |
| Presentation access | Existing presentation flow still works. | Not executed |
| Certificates | Certificate pages still work. | Not executed |
| Volunteer hours | Existing volunteer-hour fields still work if present. | Not executed |
| Portfolio | Existing portfolio flow still works if present. | Not executed |
| Existing admin actions | Admin actions work with valid CSRF. | Not executed |

