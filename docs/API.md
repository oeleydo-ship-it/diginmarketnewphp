# DiginMarket License API (v1)

REST endpoints for sellers to verify and manage licenses from inside their own products
(activation checks, update gates, purchase-code validation). All endpoints:

- Base path: `https://your-marketplace.example/api/v1/licenses`
- Method: `POST`, JSON bodies, JSON responses
- Auth: the license key itself — no separate API token
- Rate limit: 30 requests/minute per license key + IP (HTTP 429 beyond that)

The license key (`DM-XXXXXXXX-XXXXXXXX-XXXXXXXX`) is shown to the buyer on their
purchase page and doubles as the purchase code.

## POST /verify

Validate a license key, optionally pinning it to a product.

```json
{ "license_key": "DM-AAAAAAAA-BBBBBBBB-CCCCCCCC", "product_id": 42 }
```

Response `200`:

```json
{
  "valid": true,
  "status": "active",
  "product": { "id": 42, "title": "Lumina UI" },
  "license_type": "Regular License",
  "buyer_email_hash": "sha256-of-lowercased-email",
  "support_expires_at": "2027-01-16T00:00:00Z",
  "activations_used": 1,
  "activation_limit": 1
}
```

`valid` is `false` (still `200`) for suspended/revoked/refunded licenses or a
product mismatch. Unknown keys return `404`.

## POST /activate

Register an activation (e.g. a domain or machine). Fails with `422` when the
activation limit is reached. Re-activating the same `instance_id` is idempotent.

```json
{ "license_key": "…", "instance_id": "customer-site.example", "label": "Production" }
```

## POST /deactivate

Release a previously registered activation so it can be used elsewhere.

```json
{ "license_key": "…", "instance_id": "customer-site.example" }
```

## POST /update-check

Returns the newest published version alongside the version this license was
bought at.

```json
{ "license_key": "…" }
```

Response includes `current_version`, `latest_version`, `update_available`,
`support_active` and `support_expires_at`.

`update_available` is what an in-product updater should act on: **downloading a
newer version is free for the lifetime of the purchase**, and the buyer can fetch
any published version from their Downloads page. `support_active` is narrower — it
reports whether the license still entitles the buyer to seller assistance, and
`eligible` combines the two for products that only offer updates alongside
support.

## Payment webhooks (server-to-server)

Each enabled payment gateway delivers webhooks to
`POST /payments/{provider}/webhook` (`stripe`, `paypal`, `razorpay`, `paystack`).
Signatures are verified per provider; deliveries are idempotent by provider +
event id. The legacy `POST /stripe/webhook` path remains for dashboards
configured before multi-gateway support.
