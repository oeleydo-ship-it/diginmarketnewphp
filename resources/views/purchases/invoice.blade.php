<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice {{ $order->number }} — {{ \App\Models\Setting::get('marketplace.name') ?? config('app.name') }}</title>
<style>
body{margin:0;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;background:#f4f5fb;color:#1c1e2a}
.sheet{max-width:760px;margin:24px auto;background:#fff;border:1px solid #d7d9e5;border-radius:12px;padding:40px}
.top{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
h1{font-size:26px;margin:0}
.muted{color:#626576;font-size:13px}
.badge{display:inline-block;background:#e9f8f0;color:#0a7d4f;font-weight:700;font-size:11px;letter-spacing:.08em;text-transform:uppercase;border-radius:999px;padding:5px 12px}
table{width:100%;border-collapse:collapse;margin-top:28px;font-size:14px}
th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#626576;text-align:left;border-bottom:2px solid #e5e7f0;padding:8px 6px}
td{border-bottom:1px solid #eef0f6;padding:10px 6px;vertical-align:top}
.num{text-align:right;font-variant-numeric:tabular-nums}
.totals{margin-top:16px;margin-left:auto;width:280px;font-size:14px}
.totals div{display:flex;justify-content:space-between;padding:5px 6px}
.totals .grand{border-top:2px solid #1c1e2a;font-weight:700;font-size:16px;margin-top:6px;padding-top:10px}
.actions{max-width:760px;margin:0 auto 40px;display:flex;justify-content:flex-end;gap:10px}
.actions a,.actions button{border:1px solid #cfd2e1;background:#fff;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;color:#1c1e2a}
.actions button{background:#3525cd;border-color:#3525cd;color:#fff}
@media print{body{background:#fff}.sheet{border:0;margin:0;max-width:none}.actions{display:none}}
</style>
@vite(['resources/js/app.js'])
</head>
<body>
<div class="sheet">
 <div class="top">
  <div>
   <h1>{{ \App\Models\Setting::get('marketplace.name') ?? config('app.name') }}</h1>
   <p class="muted">{{ \App\Models\Setting::get('marketplace.support_email') ?? '' }}</p>
  </div>
  <div style="text-align:right">
   <span class="badge">{{ $order->payment_status === 'paid' ? 'Paid' : 'Partially refunded' }}</span>
   <p class="muted" style="margin:10px 0 0">Invoice <strong>{{ $order->number }}</strong><br>
   Date: {{ ($order->paid_at ?? $order->created_at)->format('M d, Y') }}<br>
   Payment: {{ str(str_replace('_',' ',$order->payment_provider ?? 'stripe'))->title() }}</p>
  </div>
 </div>
 <div style="margin-top:28px">
  <p class="muted" style="margin:0">Billed to</p>
  <p style="margin:4px 0 0;font-weight:600">{{ $order->user->name }}</p>
  <p class="muted" style="margin:2px 0 0">{{ $order->user->email }}</p>
 </div>
 <table>
  <thead><tr><th>Item</th><th>License</th><th class="num">Amount</th></tr></thead>
  <tbody>
   @foreach($order->items as $item)
    <tr>
     <td>{{ $item->product_title }}<br><span class="muted">by {{ $item->seller_name }}</span></td>
     <td>{{ $item->license_name }}</td>
     <td class="num">${{ number_format((float) $item->total, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <div class="totals">
  <div><span>Subtotal</span><span>${{ number_format((float) $order->subtotal, 2) }}</span></div>
  @if((float) $order->discount > 0)<div><span>Discount</span><span>−${{ number_format((float) $order->discount, 2) }}</span></div>@endif
  @if((float) $order->tax > 0)<div><span>{{ config('marketplace.tax_label', 'Tax') }}</span><span>${{ number_format((float) $order->tax, 2) }}</span></div>@endif
  <div class="grand"><span>Total ({{ $order->currency }})</span><span>${{ number_format((float) $order->total, 2) }}</span></div>
 </div>
 <p class="muted" style="margin-top:36px">Digital goods delivered electronically. Licenses and downloads are available from your account's purchases page.</p>
</div>
<div class="actions">
 <a href="{{ route('purchases.show', $order) }}">Back to purchase</a>
 <button data-print>Print / Save as PDF</button>
</div>
</body>
</html>
