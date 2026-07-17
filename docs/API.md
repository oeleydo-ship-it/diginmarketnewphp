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
activation limit is reached.

```json
{ "license_key": "…", "instance": "customer-site.example" }
```

## POST /deactivate

Release a previously registered activation so it can be used elsewhere.

```json
{ "license_key": "…", "instance": "customer-site.example" }
```

## POST /update-check

Gate updates on support status: returns the newest published version and whether
this license's support window still covers updates.

```json
{ "license_key": "…", "current_version": "1.2.0" }
```

Response includes `latest_version`, `update_available`, and `support_active`.

## Payment webhooks (server-to-server)

Each enabled payment gateway delivers webhooks to
`POST /payments/{provider}/webhook` (`stripe`, `paypal`, `razorpay`, `paystack`).
Signatures are verified per provider; deliveries are idempotent by provider +
event id. The legacy `POST /stripe/webhook` path remains for dashboards
configured before multi-gateway support.
