# Azure Architecture Standards

## Purpose

This document defines the baseline Azure architecture standards for YUVA Club.

## Baseline Services

| Service | Standard Use |
|---|---|
| Azure App Service or Container Apps | Host web/API workloads |
| Azure SQL Database | Primary relational data store |
| Azure Blob Storage | User-uploaded files, profile photos, exports, generated artifacts |
| Azure Key Vault | Secrets, signing keys, provider credentials |
| Application Insights | Telemetry, traces, errors, performance metrics |
| Azure Communication Services | Email/SMS notifications when selected |
| Azure OpenAI | AI coaching, summarization, recommendations, safety workflows |
| Background Jobs | Email delivery, exports, AI processing, dashboard initialization |

## Environment Strategy

Required environments:

- Development
- Staging
- Production

Each environment must have separate:

- Database
- Secrets
- Storage container or account
- Application Insights resource
- Deployment configuration

## Configuration Rules

- Secrets live in Key Vault, not code.
- Environment-specific settings live in environment configuration.
- Feature flags control staged rollout.
- Long-running tasks use background processing.

## Observability

Every production service must emit:

- Request count
- Error rate
- Latency
- Dependency failures
- Background job failures
- Authentication failures
- Registration funnel events
- AI processing failures

## Developer Notes

- Never store secrets in source control.
- Never use production resources from development.
- Never process AI or email tasks synchronously if they can slow student onboarding.
- Never deploy a feature without basic telemetry.

