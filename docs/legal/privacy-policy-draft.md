# YUVA Club Privacy Policy

Draft status: Internal review draft  
Last updated: July 13, 2026  
Website: https://www.yuvaclub.app  
Contact: support@yuvaclub.app

Internal review comment: This policy is a production-quality draft based on the current YUVA Club codebase. It must be reviewed by qualified legal counsel before publication, especially for child privacy, education records, international users, and data retention requirements.

## 1. Overview

YUVA Club is a youth leadership development platform for student registration, presentations, research practice, AI-assisted feedback, certificates, volunteer-hour tracking, parent visibility, organization participation, and administrator review.

This Privacy Policy explains what information YUVA Club collects, how it is used, who may access it, and how students, parents, and organizations may request help with privacy questions.

We do not sell student personal information.

## 2. Information We Collect

YUVA Club may collect the following information when a student registers or participates:

- Student name, preferred name, date of birth, age, grade, school, city/state, email, phone number, and WhatsApp contact if provided.
- Parent or guardian name, relationship, email address, and phone number.
- Membership path, including individual membership or organization participation.
- Organization invitation or join code when a student joins through an organization.
- Learning interests, presentation experience, presentation topics, reasons for joining, availability preferences, and suggestions.
- Code of Conduct agreement, recording agreement, and parent permission acknowledgement.
- Student login credentials, stored as password hashes rather than plaintext passwords.
- Research notes, sources, presentation outlines, prepared questions, and uploaded research files.
- Topic selections, attendance, presentation counts, service or volunteer hours, scores, ranks, badges, certificate status, mentor/admin feedback, AI feedback summaries, and activity records.
- Safety reports submitted through the student dashboard.
- Account activation and invitation status for parents and organization administrators.
- Security and audit data, including IP address, user agent, login attempts, session activity, role-based access checks, and administrative actions.

## 3. Parent Accounts

Parent accounts are used to view student records connected to that parent. Parent access is controlled through backend parent-student relationship checks. A parent may only access student records linked to that parent account.

Parent activation links are time-limited. Parent passwords are created by the parent and stored as password hashes.

## 4. Organization Accounts

Organizations may participate in YUVA Club through organization administrators invited by the Master Admin. Organization administrators may view and manage only student memberships assigned to their own organization.

Organizations do not own a student's global YUVA identity, certificates, portfolio, presentations, or volunteer history. Organizations manage only organization-specific membership, assignments, status, groups, coaches, teachers, moderators, and organization-specific activity permissions.

## 5. Master Admin Access

The platform Master Admin has platform-level access for administration, student approvals, organization setup, organization admin invitations, security, configuration, reports, and audit review.

Organization administrators cannot create Master Admins, change global platform settings, or access records outside their assigned organization.

## 6. AI-Assisted Practice and Feedback

YUVA Club includes AI-assisted review for student research and presentation preparation when an OpenAI API key is configured. The AI Coach may review student-submitted research notes, sources, presentation outlines, prepared questions, selected topic, and related learning information.

AI feedback is intended to support learning. It should not be treated as a final human judgment. Platform administrators may review AI output before applying official scores, feedback, ranks, or certificates.

Internal review comment: Confirm the final AI vendor, data processing terms, retention settings, and parental disclosure language before publication.

## 7. Uploads, Certificates, Portfolios, and Activities

Students may upload research or presentation preparation files in supported formats, including PDF, PowerPoint, Word documents, and images. YUVA Club may store uploaded files so students and administrators can review them.

YUVA Club may generate or display certificates, badges, rank status, presentation records, volunteer or service hours, topic selections, research status, and portfolio summaries.

## 8. Presentation Recordings

The current registration form asks students and parents to acknowledge that YUVA Club sessions may be recorded for educational purposes. The codebase includes Zoom meeting fields and protected meeting information, but does not currently implement an automated recording storage workflow in the application.

If recordings are enabled operationally, YUVA Club should limit access to authorized administrators, mentors, organization staff, parents, or students as appropriate.

Internal review comment: Recording practices require separate legal review before publication, including consent, storage location, retention period, access controls, and state/country-specific recording laws.

## 9. How We Use Information

YUVA Club uses information to:

- Register students and create student accounts.
- Create parent accounts and connect parents to students.
- Support organization membership and organization admin workflows.
- Provide student dashboards, topic selection, research submission, AI-assisted feedback, certificates, volunteer-hour tracking, and portfolio summaries.
- Review registrations, attendance, presentations, reports, safety issues, and administrative notes.
- Send account, invitation, activation, password reset, support, and notification emails.
- Protect accounts, enforce permissions, prevent unauthorized access, and maintain audit logs.
- Improve platform reliability and support.

## 10. Emails and Communications

YUVA Club may send emails from addresses such as noreply@yuvaclub.app or support@yuvaclub.app. Emails may include registration notices, account activation links, organization admin invitations, password setup or reset links, student invitations, support messages, and administrative notifications.

## 11. Cookies, Sessions, and Analytics

YUVA Club uses session cookies for login, security, and account access. Session cookies may be configured with security protections such as HttpOnly, Secure when served over HTTPS, SameSite=Lax, and strict session mode.

The current codebase does not show Google Analytics or third-party advertising trackers.

Internal review comment: If analytics, pixels, advertising, heatmaps, or third-party tracking are added later, update this policy before launch.

## 12. Hosting and Storage

YUVA Club is hosted on Azure App Service. The platform supports Azure SQL storage when configured. Some current workflows also use server-side file storage for registration CSVs, JSON records, uploads, audit logs, and invitation delivery logs.

Database connections use encrypted SQL Server connection settings when the sqlsrv driver is configured.

## 13. Sharing Information

YUVA Club may share information only as needed to operate the platform:

- With parents or guardians for students linked to their account.
- With organization administrators for students assigned to their organization.
- With platform administrators for platform operations and safety.
- With service providers that host, store, email, secure, or process platform information.
- When required for safety, legal obligations, or to protect users and the platform.

## 14. International Access

YUVA Club may be accessed from outside the United States. Platform data may be processed in the United States or other locations where YUVA Club's hosting and service providers operate.

Internal review comment: International transfer language requires legal review before publication.

## 15. Data Retention

YUVA Club retains student records, certificates, presentations, portfolio summaries, organization membership history, parent links, and audit logs as needed to operate the platform, preserve student achievements, maintain security, and support organization accountability.

Removing a student from an organization does not delete the student's global YUVA identity or historical achievement records.

Internal review comment: Final retention schedules are not yet implemented in code. Define and approve retention periods before publication.

## 16. Account Deletion and Privacy Requests

Students, parents, or authorized representatives may contact support@yuvaclub.app to request access, correction, deletion, or other privacy assistance.

Some records may be retained where needed for security, audit, legal, safety, certificate history, organization accountability, or platform integrity.

Internal review comment: Build and document a formal request workflow before publication, including identity verification, response timelines, and exceptions.

## 17. Children's and Student Privacy

YUVA Club is designed for youth learning and collects parent or guardian information and consent acknowledgement during registration. Parents may request assistance by contacting support@yuvaclub.app.

Internal review comment: Do not publish child privacy compliance claims until counsel reviews COPPA, FERPA, state privacy laws, school/organization contracts, and international student privacy requirements.

## 18. Security

YUVA Club uses role-based access controls, CSRF protection for sensitive forms, session security settings, password hashing for activated accounts, login attempt tracking, organization isolation, and audit logging for sensitive actions.

No system can guarantee perfect security. Users should keep passwords confidential and report suspected unauthorized access.

## 19. Changes to This Policy

YUVA Club may update this Privacy Policy as the platform changes. The updated version should show a revised date and should be reviewed before publication.

