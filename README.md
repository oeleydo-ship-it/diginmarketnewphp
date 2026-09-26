# DiginMarket

A multi-vendor marketplace for licensed digital products built with Laravel 12, Blade, Tailwind CSS, and Stripe (Checkout, Webhooks, Connect). Sellers submit versioned products through admin moderation; customers buy, download, and manage licenses; finances flow through an immutable wallet ledger with commissions, clearance, refunds, disputes, and Stripe transfer payouts.

The implementation blueprint lives in [MARKETPLACE_BUILD_PLAN.md](MARKETPLACE_BUILD_PLAN.md).

## Requirements

- PHP 8.3+ with the `pdo_sqlite` / `pdo_mysql`, `mbstring`, `openssl`, and `curl` extensions
- Composer 2
- Node 20+ / npm
- MySQL 8+ in production (SQLite is used for local development and in-memory tests)
- A Stripe account with Connect enabled

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # .env defaults to sqlite
php artisan migrate --seed       # seeds roles + one admin user
```

Run the app with two dev servers (also configured in `.claude/launch.json`):

```bash
php artisan serve        # app on http://localhost:8000
npm run dev              # Vite assets on http://127.0.0.1:5173
```

Process queued work (emails, jobs) and the scheduler locally:

```bash
php artisan queue:work
php artisan schedule:work
```

### Redis, Horizon, and Reverb

The app still defaults to SQLite-friendly `database` queue / cache / session drivers so a fresh local clone works without Redis. To enable real-time notifications and Redis-backed workers, set:

```bash
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
# Optional once Redis is available:
# CACHE_STORE=redis
# SESSION_DRIVER=redis
```

Then start the supporting processes:

```bash
redis-server
php artisan reverb:start
php artisan horizon
```

Admins will then see the existing admin notification bell update in real time for new marketplace alerts (orders, seller applications, reviews, refunds, disputes, withdrawals, subscriptions).

Horizon requires the `pcntl` and `posix` PHP extensions, so it typically runs on Linux/macOS servers. On Windows, keep using `php artisan queue:work` locally unless your PHP runtime provides those extensions.

## Testing

```bash
php artisan test
```

Tests run on in-memory SQLite (forced in `phpunit.xml` — do not remove those env lines, or `RefreshDatabase` will wipe `database/database.sqlite`). Stripe is never called in tests: checkout, refund, and payout gateways are contracts (`app/Contracts`) bound to fakes per test.

## Stripe configuration

| Env var | Purpose |
|---|---|
| `STRIPE_KEY` | Publishable key |
| `STRIPE_SECRET` | Secret key (server-side API calls) |
| `STRIPE_WEBHOOK_SECRET` | Signing secret for the webhook endpoint |

1. Create a webhook endpoint pointing at `POST /stripe/webhook` subscribed to `checkout.session.completed`. Events are persisted and processed idempotently — replays are safe.
2. Enable Stripe Connect (Express). Sellers onboard from their dashboard; payouts to sellers are Stripe transfers created when an admin approves a withdrawal.
3. Locally, forward events with `stripe listen --forward-to localhost:8000/stripe/webhook`.

## Marketplace tuning

| Env var | Default | Purpose |
|---|---|---|
| `MARKETPLACE_COMMISSION_RATE` | 20 | Global commission % (overridable per category/seller/product via commission rules) |
| `EARNINGS_CLEARANCE_DAYS` | 14 | Days before pending seller earnings clear to available |
| `MINIMUM_WITHDRAWAL` | 50 | Minimum withdrawal amount |
| `WITHDRAWAL_FEE_RATE` | 0 | Withdrawal fee % |

## Production deployment

1. **Build**: `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
2. **Configure** `.env`: `APP_ENV=production`, `APP_DEBUG=false`, MySQL credentials, real mail transport, Stripe live keys. Then `php artisan config:cache route:cache view:cache`.
3. **Migrate**: `php artisan migrate --force`
4. **Queue worker** (required — emails and jobs are queued): run `php artisan queue:work --tries=3` under a supervisor (systemd/Supervisor), restart on deploy with `php artisan queue:restart`.
5. **Scheduler** (required — earnings clearance runs daily): add the cron entry `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
6. **Storage**: product archives live on the private local disk (or S3 via `FILESYSTEM_DISK`); they are only ever served through signed, license-checked download routes. Run `php artisan storage:link` for public branding uploads.
7. **Security**: HTTPS is required (HSTS is emitted on secure responses); a CSP and hardened headers are applied by `App\Http\Middleware\SecurityHeaders`.

### Web installer

Fresh deployments without shell access can install from the browser: visiting any page
before installation redirects to `/install`. If `.env` is absent, the installer copies
`.env.example`, switches to production mode with debug disabled, and generates a unique
application key. Step 1 configures the database (MySQL/MariaDB credentials are verified
with a live connection before being written to `.env`, or SQLite for small sites);
step 2 creates the marketplace name and superadmin account, runs migrations, seeds
roles/license types, and writes the install lock (`storage/app/installed.lock`). Point
the web server at `public/` and make `.env`, `storage/`, and `bootstrap/cache/` writable.
Keep the install lock across deployments; if it is lost, the installer refuses to create
another superadmin in a database that already contains users.

### Updating (upload a release zip)

Admins apply updates from **Admin → System health → Application update** by uploading a
release zip — the CodeCanyon-style flow, no shell required:

1. The archive is validated: no path traversal, and only application paths (`app/`, `config/`,
   `resources/`, `routes/`, `database/`, `vendor/`, …) may be touched. `.env`, `storage/`, and
   uploaded files are never modified.
2. A database backup is taken, the site enters maintenance mode, files are replaced,
   `php artisan migrate --force` runs, caches are cleared, and the site comes back up.
3. An optional `update-manifest.json` (`{"version": "x.y.z"}`) at the zip root records the new
   version, shown on the System health page. Every update is written to the audit log.

Zips that wrap everything in a single top-level folder (GitHub-style archives) are handled
automatically.

### Backup & restore

- Back up the database and `storage/app` (private product files) together; licenses, orders, and ledger entries are only meaningful alongside the files they gate.
- Restore drill: restore the DB dump, restore `storage/app`, run `php artisan config:clear`, and verify `/up` returns 200 and a test download succeeds.

## Operational notes

- Money is stored as fixed-precision decimals; seller balances change only through immutable `wallet_transactions` rows written inside DB transactions.
- Sensitive admin actions (approvals, rejections, payouts, settings changes) are written to `audit_logs` and visible at `/admin/audits`.
- The health endpoint is `/up`; the license verification API is under `/api/v1/licenses` (license-key auth, rate-limited) — full reference in [docs/API.md](docs/API.md).
- Product uploads are scanned before download release: set `CLAMAV_PATH` to a ClamAV binary for real malware scanning; without it a zip-integrity check runs and anything suspicious is flagged for admin review.
