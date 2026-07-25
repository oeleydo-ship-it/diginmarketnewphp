# Code review — 25 July 2026

Review of the DiginMarket marketplace against `MARKETPLACE_BUILD_PLAN.md`, focused on the money,
fulfilment and entitlement paths. Nine defects were found and fixed, and the product-update flow
they exposed was built out into a full feature. The suite went from 273 to 290 passing tests.

Branch: `feature/codecanyon-parity`.

---

## Bugs fixed

### 1. A refund or dispute on one item revoked the whole order

**Severity: high — silently removes access the buyer paid for.**

`RefundService::approve()` and `DisputeService::uphold()` set the *order's* `payment_status` to
`partially_refunded` / `disputed`. Every entitlement check in the app compared that field against
the literal string `'paid'`:

| Check | File |
|:--|:--|
| Secure download | `DownloadController` |
| Licence verify / activate / update-check API | `LicenseActivationService::findUsableLicense()` |
| Leaving a review | `ReviewService::create()` |
| Opening a support ticket | `SupportService::open()` |
| Viewing the invoice | `PurchaseController::invoice()` |

So refunding one line of a five-line order killed downloads, licence activations, support and the
invoice for the other four — all of which the buyer had paid for and kept. The affected item was
already handled correctly by its own licence status (`refunded` / `revoked` / `suspended`).

**Fix.** `Order::SETTLED_STATUSES` plus `isSettled()` and a `settled()` query scope now express
"the money reached us", and every entitlement check uses it. Per-item revocation stays where it
belongs: on the licence.

### 2. Refunding one item marked a whole order refunded (and vice versa)

`approve()` unconditionally wrote `partially_refunded`, so refunding the *only* item of an order
still called it partial. `settleOrderState()` now counts refunded lines and writes `refunded` only
when every line is refunded. `DisputeService::uphold()` does the same, counting upheld disputes and
refunds together.

### 3. A double-clicked approval refunded the buyer twice at the payment provider

**Severity: high — real money leaves the account with no record of it.**

`approve()` called `$this->gateway->refund()` *before* any status guard. The guard inside the
transaction stopped the second ledger entry, but by then the provider had already been told to
refund twice. Concurrent or double-submitted approvals paid out twice and recorded once.

**Fix.** The request is claimed with a locked `submitted|under_review → processing` transition
*before* the provider is contacted; a second call finds nothing to claim and returns. If the
provider answers with a refusal (`RuntimeException` / `ValidationException` — no money moved) the
claim is released to the exact status it had. If the call fails ambiguously (timeout, dropped
connection — the refund may have gone through) the request stays `processing` and stays visible in
the admin queue behind a warning telling the admin to reconcile with the provider dashboard before
approving again.

### 4. Refunds short-changed the buyer by the tax

Tax is charged on top of the discounted subtotal and allocated pro-rata into `order_items.tax`,
while item `total` stays net so commissions never touch tax money. `request()` used
`$item->total` as the requested amount, so a $100 item bought at $110 including tax was only ever
refunded $100. `RefundService::refundableAmount()` now returns `total + tax`. The seller-side
claw-back is unchanged and still capped at `seller_earning` — platform commission and the buyer's
tax are not the seller's money to return.

### 5. A failed webhook delivery could never be retried

**Severity: high — a paid order stays unfulfilled forever.**

`PaymentWebhookService::handle()` returned early when `! $record->wasRecentlyCreated`. Providers
redeliver failed webhooks for days, but every redelivery hit the existing row and bailed out. One
transient failure — a deadlock, a mail hiccup — permanently stranded a paid order with no licence.

**Fix.** Only `processed` short-circuits. Other states are claimed with a conditional `UPDATE`, so
concurrent deliveries still cannot both process one event, and a claim abandoned by a crashed
worker goes stale after five minutes and becomes retryable.

### 6. Approving a listing republished versions an admin had rejected

`ProductSubmissionService::submit()` swept *every* version into `pending_review`, including live
ones and previously rejected uploads; `approve()` then published *every* version. So editing a
published listing pulled the live release out of buyers' reach mid-review, and approval quietly
resurrected rejected uploads. Submit now only promotes `draft`/`processing` versions and approve
only publishes `pending_review` ones.

### 7. Download crashed with a 500 when a version had no file

`$license->version?->files()->…->firstOrFail()` short-circuits the whole chain to `null` when the
licence has no version, then dereferences it. Missing versions and file-less versions now return
404s.

### 8. Duplicate refund requests hit a database constraint

`refund_requests.order_item_id` is unique, so a buyer submitting a second request for the same item
got a 500 from the constraint violation. It is now a validation message.

### 9. Refund approvals were not audited

Rejections wrote an `AuditLog` row; approvals — the ones that move money — did not. The plan
requires sensitive administration to be audited. `refund.approved` is now recorded with the amount
and the provider's refund id.

### Also: seller metrics dropped whole orders after a partial refund

`SellerDashboardController`, `SellerSalesController` and `SellerCustomerController` counted only
`payment_status = 'paid'`, so one buyer refunding one line erased the seller's *other* sales in that
order from their dashboard KPIs, customer list and totals — while the wallet still (correctly) held
the money. `SellerSalesController` was already inconsistent with itself: the list used
`['paid','partially_refunded']` and the KPI cards above it used `'paid'`. All three now use
`Order::scopeSettled()`.

**Not changed:** `Admin\ReportController` revenue still counts `paid` orders only. Making those
figures net-of-refunds is a reporting change rather than a bug fix, and is called out below.

---

## New feature: product updates reach the people who bought the product

The bug review surfaced a functional hole. A licence stores `product_version_id` — the version at
purchase — and the download served exactly that file forever. The licence API's `update-check`
endpoint cheerfully announced `update_available: true`, but there was no route, link or button
anywhere that could deliver the new version. Buyers on a marketplace that advertises free lifetime
updates could not get a single one.

### Versioned downloads

- `GET /downloads/{license}/{version?}` — the version is optional; omitted means "the newest version
  this licence is entitled to". Still signed, still short-lived, still logged.
- Entitlement lives on the model: `License::downloadableVersions()` returns every **published**
  version of the product, newest first, plus the version snapshotted at purchase (which the buyer
  keeps even after the seller supersedes it). `canDownloadVersion()` enforces it on every request —
  unpublished versions and other products' versions 404.
- Updates are free for the lifetime of the purchase. Support expiry governs seller *assistance*, not
  access to new builds, which matches how the marketplace is sold.
- The download log now records the version actually fetched, not the version bought.

### Buyer download library — `GET /downloads`

A new sidebar entry between Purchases and Wishlist. Lists every licence with its full version
history, marks the release the buyer purchased and the current latest, flags "Update available",
disables downloads for refunded/revoked/suspended licences, and shows a paginated download history
(product, version, timestamp, IP).

### Update notices on the purchase page

`/purchases/{order}` now downloads the newest entitled version by default (the button names the
version), shows an "Update available" badge, and expands an "All versions" panel with each release's
title, notes and date.

### Email when a new version is approved

`ProductSubmissionService::approveVersion()` dispatches `NotifyBuyersOfProductUpdate`, which mails
every holder of an active licence for that product — de-duplicated per user, skipping unsettled
orders, and honouring the `email_product_updates` notification preference that existed in the schema
but was never wired to anything.

---

## Tests

17 new tests across two files; the full suite is 290 passing.

`tests/Feature/DownloadsAndUpdatesTest.php` (9)
: unversioned download serves the newest release · the purchased version stays fetchable · pending
versions and other products' versions are refused · a file-less version 404s instead of 500s · the
library page lists versions and history · the purchase page offers every entitled version · approval
queues update mail · opted-out buyers get nothing · resubmitting a listing leaves published versions
published and rejected ones rejected.

`tests/Feature/RefundAndWebhookResilienceTest.php` (8)
: refunding one item leaves siblings downloadable, verifiable and invoiceable · refunding every item
marks the order refunded · the request covers tax · a second approval never calls the provider twice
· duplicate requests are rejected · an upheld dispute spares the other items · a failed webhook is
processed on redelivery · an already-processed webhook is still ignored.

---

## Known gaps worth a follow-up

- **Net-of-refunds reporting.** Admin revenue reports count paid orders at full value and exclude
  partially refunded ones entirely. Neither is right: revenue should be gross minus refunded line
  amounts. That needs a reporting model of its own.
- **Coupon over-redemption race.** `CouponService::validate()` checks `max_uses` at cart time;
  `redeem()` does not re-check at fulfilment, so simultaneous checkouts can exceed the cap by a few.
- **Bundle order items carry no tax split.** `DirectCheckoutService::startBundle()` puts tax on the
  order but leaves `order_items.tax` at zero, so bundle refunds fall back to the net amount.
- **Refunding a support extension does not shorten the support window.** The item carries no licence
  of its own (it points at an existing one through `license_id`), so `approve()` refunds the money
  and revokes nothing — the buyer keeps the extra months.
