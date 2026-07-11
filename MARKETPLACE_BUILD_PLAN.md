# Digital Product Marketplace — Build Blueprint

## Purpose

Build a production-ready multi-vendor marketplace for licensed digital products with Laravel 12, PHP 8.3+, MySQL 8+, Blade, Tailwind CSS, Alpine.js, queues, private storage, and Stripe Checkout/Payment Intents, Webhooks, and Connect. This blueprint is the implementation source of truth; work ships in tested vertical slices, never placeholder-only pages.

## Non-negotiable rules

- Keep controllers thin; typed services/actions own workflows and calculations.
- Use Form Requests for validation and Policies/Gates for authorization.
- Ignore browser-supplied prices and recalculate totals, discounts, tax, fees, and commissions server-side.
- Confirm payments only through verified, idempotently processed Stripe webhooks.
- Require a paid eligible order item, active license, approved version, and limit checks for downloads.
- Keep product files private and expose only short-lived authorized download responses.
- Store money as fixed-precision decimals with ISO currency codes, never floating point.
- Change seller balances only through immutable ledger entries inside database transactions.
- Preserve financial, webhook, audit, order, refund, payout, and license history.
- Never let sellers approve their own accounts, products, versions, refunds, or withdrawals.
- Permit reviews only for verified eligible purchases.
- Encrypt sensitive settings and identity data and audit sensitive administration.
- Queue slow/retryable work and use Laravel Scheduler for recurring workflows.

## Roles

### Administrator

Operates users, sellers, submissions, catalog, orders, payments, refunds, disputes, commissions, wallets, withdrawals, content, reports, settings, audits, backups, queues, and maintenance. Impersonation is time-bound and audited.

### Seller

Completes onboarding and Stripe Connect verification, manages a storefront, submits products and versions, supports customers, reviews sales/analytics, and requests payouts.

### Customer

Browses and buys products, downloads eligible versions, manages licenses/invoices, reviews purchases, asks questions, opens support/refund requests, and maintains wishlists, collections, and followed sellers. A user may also be a seller.

## Architecture

- **HTTP:** routes, middleware, controllers, Form Requests, API Resources.
- **Application:** actions/services such as `SubmitProduct`, `CreatePendingOrder`, `ConfirmStripePayment`, `GenerateLicenses`, and `RequestWithdrawal`.
- **Domain:** Eloquent models, enums, policies, value objects, calculators, events, and invariants.
- **Infrastructure:** Stripe gateways, storage, virus scanning, mail, queues, exports, and reports.

Repositories are added only for meaningful abstraction; focused Eloquent scopes/query objects handle normal queries.

```text
Product draft -> submitted -> admin review -> approved -> published

Cart -> server pricing -> pending order -> Stripe session -> signed webhook
     -> paid order -> licenses -> ledger entries -> download access

Sale -> pending credit -> clearance -> available seller balance
Refund/dispute -> restrict fulfilment + compensating ledger entries
Withdrawal -> reserve funds -> review -> Stripe transfer/payout -> paid/reversal
```

## Domain modules

1. Authentication, security, users, roles, permissions, and audit logs
2. Seller onboarding, verification, storefronts, badges, follows, and Stripe Connect
3. Categories, attributes, tags, products, media, files, versions, prices, and moderation
4. Search, filters, ranking, wishlists, collections, and recommendations
5. Carts, coupons, taxes, checkout, payments, orders, invoices, refunds, and disputes
6. Licenses, activations, certificates, secure downloads, and updates
7. Commissions, seller wallets, immutable ledger, clearance, withdrawals, and payouts
8. Reviews, comments, support, notifications, email templates, and affiliates
9. CMS, blog, menus, homepage, SEO, reports, settings, installer, and maintenance

## Data design

Implement the normalized tables in the source brief across these groups:

- Identity: users, roles, permissions, pivots, login activity, seller profiles/documents/badges/followers.
- Catalog: categories/attributes/options, products/attributes/tags/images/files, versions/version files/review submissions, license types, and prices.
- Commerce: carts/items, coupons/applicability/usage, orders/items, payments/attempts, webhook events, billing and tax snapshots.
- Fulfilment: licenses, activations, downloads, invoices, reviews/votes, comments/reports, support, refunds, and disputes.
- Finance: seller wallets, immutable transactions, commission rules, withdrawals, connected accounts, and affiliate ledgers.
- Operations: notifications/preferences, pages, blog, menus, templates, settings, reports/exports, and immutable audit logs.

Use foreign keys, targeted indexes, uniqueness constraints, and soft deletes where recovery is meaningful. Orders store product, seller, pricing, tax, discount, fee, commission, currency, and license snapshots. Stripe event IDs, order numbers, license keys, and public slugs are unique. Statuses use backed PHP enums.

## Stripe and accounting

1. Reprice the cart and create a pending order on the server.
2. Create a Stripe session containing the internal order identifier.
3. Persist payment attempts and signed webhook payloads.
4. Claim each unique event and process it transactionally.
5. On confirmed payment, complete the order once, generate licenses once, create ledger entries once, and dispatch jobs after commit.
6. Resolve commission with configurable precedence, defaulting to product > seller > category > global.
7. Clear pending earnings through paired ledger entries.
8. Reserve withdrawal funds before review; create reversals on failure.
9. Process refunds/disputes with compensating entries and atomic fulfilment restrictions.

## Files, versions, licenses, and downloads

- Assemble chunked uploads into quarantine/private storage.
- Validate extension, MIME, size, checksum, randomized name, duplicate state, and pluggable malware scan result.
- Make only approved versions downloadable.
- Generate cryptographically strong license keys and customer certificates.
- Offer optional rate-limited, logged Sanctum endpoints for verification, activation, deactivation, support, and update eligibility.
- Log downloads with user, product, version, order, license, IP, user agent, and timestamp.

## User-facing applications

### Public marketplace

Configurable homepage, category/product browsing, full-text search and filters, product details, seller storefronts, curated lists, CMS/blog/legal/help pages, sitemaps, canonical metadata, and structured data.

### Customer dashboard

Purchases, downloads, licenses, certificates, orders, invoices, reviews, comments, tickets, refunds, wishlists, collections, follows, billing, notifications, profile, sessions, and security.

### Seller dashboard

Sales metrics, products/versions, submissions, customers, earnings, ledger, withdrawals, coupons, engagement, support, analytics, storefront, Stripe Connect, tax, notifications, and profile.

### Administrator panel

Operational metrics and complete management of identity, sellers, catalog, submissions, commerce, finance, moderation, content, reports, settings, audits, backups, queues, logs, and maintenance.

## Delivery phases

### Phase 1 — Foundation

Laravel installation, authentication, email verification, password confirmation, roles/permissions, layouts, settings, categories/attributes, profiles, audits, factories, and seed users.

**Gate:** authorization tests pass and every role reaches only permitted functional dashboards.

### Phase 2 — Seller and product system

Seller onboarding/verification, connected-account state, product CRUD, private uploads, media, versions, submissions, moderation, and storefront basics.

**Gate:** an approved seller submits a complete product/version; an admin requests changes or approves it; unpublished content cannot leak.

### Phase 3 — Marketplace

Homepage sections, browse, MySQL full-text search abstraction, filters/sorting, product pages, seller storefronts, wishlists/follows, rankings, CMS, and baseline SEO.

**Gate:** approved inventory is discoverable through responsive accessible pages with functional controls.

### Phase 4 — Commerce and fulfilment

Cart, coupons, taxes, server pricing, Stripe checkout/webhooks, orders, invoices, licenses, private downloads, and notifications.

**Gate:** a test webhook produces exactly one paid order with correct snapshots, licenses, ledgers, invoice, and download access; replay changes nothing.

### Phase 5 — Seller finance

Commission hierarchy, wallets, clearance scheduler, Connect, payouts, withdrawals, reversals, and finance reports.

**Gate:** balances reconcile to the ledger through sales, clearance, refunds, disputes, withdrawals, and failures.

### Phase 6 — Engagement and service

Verified reviews, comments, support SLAs, refunds, preferences, update notifications, and collections.

**Gate:** policies are tested and refunds consistently update Stripe state, orders, licenses, downloads, and ledgers.

### Phase 7 — Administration and growth

Full admin operations, disputes, affiliates, CMS/blog/menus, email templates, exports, audits, health, backups, maintenance, and installer.

**Gate:** admins operate completed workflows without direct database access and sensitive actions are audited.

### Phase 8 — Production hardening

Caching, queues, scheduler coverage, CSP/security review, performance/N+1 work, accessibility, dark mode, comprehensive tests, API/deployment docs, and backup/restore drills.

**Gate:** tests pass and production setup, Stripe webhooks, workers, cron, storage, backup, restore, update, and rollback are verified.

## Test strategy

- Feature tests: auth, onboarding, moderation, visibility, pricing, coupons, tax, checkout, webhook idempotency, fulfilment, reviews, refunds, withdrawals, and authorization.
- Unit tests: money, commission priority, tax, coupon eligibility, wallet transitions, license eligibility, and rankings.
- Stripe integration uses gateway contracts and signed fixtures/fakes; never frontend payment simulation.
- Storage tests prove private files cannot be fetched without authorization.
- Realistic deterministic factories/seeders cover admins, customers, sellers, catalog, orders, reviews, coupons, tickets, and settings.

## Operational deliverables

- Detailed `.env.example` without credentials.
- Local/production setup and a web installer with installation lock.
- MySQL, Redis/database queue, cron, workers, email, private local/S3 storage, Stripe, Webhooks, Connect, and optional Stripe Tax documentation.
- Deployment, update, rollback, backup/restore, troubleshooting, and security guidance.
- Versioned Sanctum API documentation.
- Admin queue, failed-job, storage, backup, and system-health visibility.

## Definition of done

A feature is complete only when its schema, relationships, domain action/service, validation, authorization, functional UI/API, applicable audits/notifications, and automated tests exist. Historical and monetary values must be snapshotted or ledgered. Responsive empty/loading/error states must exist. No major control or dashboard section may be decorative or placeholder-only.

## Initial implementation order

1. Scaffold Laravel 12 and Blade/Tailwind/Alpine.
2. Add authentication, verification, password confirmation, sessions, and two-factor-ready structure.
3. Add roles, permissions, dashboard routing, policies, and audit logs.
4. Add typed settings and category/custom-attribute management.
5. Add seller onboarding and approval.
6. Add product/version/private-file submission and moderation.
7. Continue through each phase gate while keeping the app runnable and tested.
