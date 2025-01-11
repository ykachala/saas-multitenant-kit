# saas-multitenant-kit

**Production-grade multi-tenant SaaS boilerplate. Tenant isolation, plan-based feature gating, billing integration, and a full REST API scaffold — ready to build on.**

![PHP](https://img.shields.io/badge/PHP_8.2+-777BB4?style=flat&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel_11-FF2D20?style=flat&logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-336791?style=flat&logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-DC382D?style=flat&logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=flat&logo=docker&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-009639?style=flat&logo=nginx&logoColor=white)
![GitHub Actions](https://img.shields.io/badge/CI%2FCD-2088FF?style=flat&logo=github-actions&logoColor=white)
![Paystack](https://img.shields.io/badge/Paystack-00C3F7?style=flat)
![Stripe](https://img.shields.io/badge/Stripe-635BFF?style=flat&logo=stripe&logoColor=white)
[![CI](https://github.com/ykachala/saas-multitenant-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/ykachala/saas-multitenant-kit/actions/workflows/ci.yml)

---

## What this is

Every SaaS company builds multi-tenancy from scratch and makes the same mistakes: leaky tenant scoping, global query contamination, billing wired too late, no feature gating until a customer asks.

This kit solves those problems before you write a line of product code. It's the architectural foundation I've built (and rebuilt) across multiple production SaaS products — extracted into a clean, documented, opinionated boilerplate.

**If you're starting a SaaS product, clone this and ship your first feature on day one.**

---

## Architecture

```
                    ┌──────────────────────────────────────┐
                    │           Nginx (reverse proxy)       │
                    │  tenant-a.yourapp.com → app:8080      │
                    │  tenant-b.yourapp.com → app:8080      │
                    └────────────────┬─────────────────────┘
                                     │
                                     ▼
                    ┌──────────────────────────────────────┐
                    │         Laravel Application           │
                    │                                       │
                    │  ┌────────────────────────────────┐  │
                    │  │  TenantResolutionMiddleware     │  │
                    │  │  - Reads subdomain / header     │  │
                    │  │  - Loads tenant from cache      │  │
                    │  │  - Scopes DB connection         │  │
                    │  │  - Sets feature flags           │  │
                    │  └────────────┬───────────────────┘  │
                    │               │                        │
                    │  ┌────────────▼───────────────────┐  │
                    │  │  Global Query Scope (Eloquent)  │  │
                    │  │  All queries auto-filtered by   │  │
                    │  │  tenant_id — no manual where()  │  │
                    │  └────────────┬───────────────────┘  │
                    │               │                        │
                    │  ┌────────────▼───────────────────┐  │
                    │  │  Feature Gate                   │  │
                    │  │  Plan → features map            │  │
                    │  │  Usage limits enforced here     │  │
                    │  └────────────────────────────────┘  │
                    └──────────┬───────────────────────────┘
                               │
                    ┌──────────┴──────────┐
                    ▼                     ▼
              PostgreSQL               Redis
           (shared schema,          (tenant config cache,
            tenant_id column)        rate limits, queues)
```

**Isolation strategy:** Shared schema + `tenant_id` column. The global query scope auto-applies `WHERE tenant_id = ?` on every Eloquent query — no manual scoping required.

---

## Tech stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 / PHP 8.2+ |
| Database | PostgreSQL 15 |
| Cache | Redis 7 |
| Queue | Laravel Horizon (Redis-backed) |
| Auth | Laravel Sanctum (API tokens + session) |
| Billing | Paystack + Stripe (both drivers included) |
| Background jobs | Laravel Queues + Horizon dashboard |
| Reverse proxy | Nginx (wildcard subdomain config included) |
| Containerisation | Docker + Docker Compose |
| CI/CD | GitHub Actions (test → lint → build) |
| Testing | Pest PHP — unit, feature, and integration tests |

---

## Features

### Tenant management
- Tenant registration with subdomain provisioning
- Subdomain and custom domain routing (Nginx config included)
- Tenant suspension and activation (cascade delete via DB foreign key)
- Per-tenant configuration storage (timezone, locale, branding)
- Tenant context available anywhere via `tenant()` helper

### Data isolation
- Global Eloquent scope auto-applies `WHERE tenant_id = ?` on all queries
- Middleware enforces tenant resolution before any controller logic
- Tenant resolved from subdomain, custom domain, or `X-Tenant-ID` header
- Redis cache for tenant lookups (5-minute TTL, invalidated on update)

### Authentication & authorisation
- Multi-tenant user model (users belong to tenants, not global)
- Role-based access control: Owner, Admin, Member, custom roles
- API token auth (Sanctum) with per-token scopes
- Invite-based user onboarding with secure random tokens (7-day expiry)

### Billing & plans
- Plan model: Free, Starter, Pro, Enterprise (fully configurable)
- Feature flags tied to plan: `$tenant->can('feature_name')`
- Usage limits tied to plan: `$tenant->withinLimit('seats', count($users))`
- Paystack integration: subscription creation, webhook handling, plan sync
- Stripe integration: identical interface, swap driver in config
- Billing portal endpoints (subscribe, cancel, invoice list)
- Failed payment webhook handling — suspends tenant on non-payment

### API scaffold
- Versioned REST API (`/api/v1/...`)
- Consistent response envelope: `{ data, meta, errors }`
- Pagination on all collection endpoints
- Request validation inline in controllers (Form Request classes are the extension point)
- Rate limiting per tenant (configurable)
- API documentation via Scribe (auto-generated from code)

### DevOps
- Full Docker Compose stack: app, postgres, redis, nginx, horizon
- `.env.example` with all variables documented
- GitHub Actions: lint (Pint), static analysis (PHPStan level 6), tests, build
- Makefile with common commands (`make test`, `make migrate`, `make horizon`)
- Health check endpoint for load balancer integration

---

## Getting started

```bash
git clone https://github.com/ykachala/saas-multitenant-kit.git
cd saas-multitenant-kit
cp .env.example .env
docker compose up -d
docker compose exec app php artisan migrate --seed
```

App: `http://localhost:8080`  
Horizon dashboard: `http://localhost:8080/horizon`

```bash
# Run tests
docker compose exec app php artisan test

# Or with Pest directly
docker compose exec app ./vendor/bin/pest --coverage
```

### Local subdomain setup

Add to `/etc/hosts`:
```
127.0.0.1 tenant-a.localhost
127.0.0.1 tenant-b.localhost
```

Then access `http://tenant-a.localhost:8080` — the middleware resolves the tenant automatically.

---

## Project structure

```
saas-multitenant-kit/
├── app/
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── ResolveTenant.php          # Core tenant resolution
│   │   │   └── EnforceFeatureGate.php     # Plan/feature enforcement
│   │   └── Controllers/Api/V1/
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php                       # Scoped to tenant
│   │   └── Concerns/BelongsToTenant.php   # Reusable trait
│   ├── Services/
│   │   ├── TenantService.php
│   │   ├── BillingService.php             # Billing driver interface
│   │   └── Billing/
│   │       ├── PaystackDriver.php
│   │       └── StripeDriver.php
│   └── Scopes/
│       └── TenantScope.php                # Global Eloquent scope
├── database/
│   ├── migrations/                        # Ordered, documented
│   └── seeders/                           # Demo tenant + data
├── routes/api.php                         # Versioned API routes
├── config/tenancy.php                     # Tenancy configuration
├── nginx/                                 # Nginx wildcard config
├── docker-compose.yml
├── Makefile
└── .github/workflows/
    └── ci.yml
```

---

## Customising

This is a starting point, not a constraint. Common customisations:

- **Switch isolation strategy** — set `TENANCY_MODE=schema` in `.env` for per-schema isolation
- **Add a billing provider** — implement `BillingDriverInterface`, register in `config/tenancy.php`
- **Add feature flags** — define in `config/features.php`, gate with `$tenant->can('flag')`
- **Add a custom domain** — store in `tenants.custom_domain`, Nginx cert provisioning via certbot

---

## Why this exists

I've built multi-tenant SaaS from scratch multiple times. The first time takes weeks to get right. The second time, you make different mistakes. By the third, you know exactly what the foundation needs to look like.

This is that foundation. The architectural decisions here are deliberate, documented, and production-hardened.

---

## Related

- [nexus-scheduler](https://github.com/ykachala/nexus-scheduler) — AI scheduling engine that can be embedded into this SaaS kit  
- [hookstream](https://github.com/ykachala/hookstream) — webhook delivery engine for outbound events

---

**Author:** Yoweli Kachala &nbsp;|&nbsp; [LinkedIn](https://linkedin.com/in/yoweli-kachala) &nbsp;|&nbsp; Cape Town, South Africa
