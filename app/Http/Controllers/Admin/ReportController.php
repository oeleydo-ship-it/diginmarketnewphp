<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RefundRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        [$from, $to] = $this->range();
        $paidOrders = $this->paidOrders($from, $to);
        $paidItems = OrderItem::whereHas('order', fn (Builder $q) => $this->applyPaidFilter($q, $from, $to));

        $grossRevenue = (float) (clone $paidOrders)->sum('total');
        $orderCount = (clone $paidOrders)->count();
        $commission = (float) (clone $paidItems)->sum('platform_commission');
        $summary = [
            'gross_revenue' => $grossRevenue,
            'orders' => $orderCount,
            'items_sold' => (clone $paidItems)->count(),
            'average_order' => $orderCount ? round($grossRevenue / $orderCount, 2) : 0.0,
            'platform_commission' => $commission,
            'seller_earnings' => (float) (clone $paidItems)->sum('seller_earning'),
            'refund_requests' => RefundRequest::whereBetween('created_at', [$from, $to])->count(),
            'open_disputes' => Dispute::whereIn('status', ['open', 'under_review'])->count(),
            'unique_customers' => (clone $paidOrders)->distinct('user_id')->count('user_id'),
        ];

        // Month buckets are built in PHP from (paid_at, total) rows so the same code runs on
        // SQLite (dev/tests) and MySQL (production) without dialect-specific date functions.
        $months = collect(range(11, 0))->map(fn (int $back) => now()->subMonths($back)->format('Y-m'));
        $rows = Order::query()->where('payment_status', 'paid')->where('paid_at', '>=', now()->subMonths(12)->startOfMonth())->get(['paid_at', 'total']);
        $byMonth = $rows->groupBy(fn ($order) => $order->paid_at->format('Y-m'))->map(fn ($group) => (float) $group->sum('total'));
        $trend = $months->map(fn (string $month) => ['month' => Carbon::createFromFormat('Y-m', $month)->format('M y'), 'revenue' => (float) ($byMonth[$month] ?? 0)]);
        $trendMax = max(1.0, (float) $trend->max('revenue'));

        $topProducts = (clone $paidItems)->selectRaw('product_id, product_title, count(*) as sales, sum(total) as revenue, sum(platform_commission) as commission')
            ->groupBy('product_id', 'product_title')->orderByDesc('revenue')->limit(10)->get();
        $topSellers = (clone $paidItems)->selectRaw('seller_id, seller_name, count(*) as sales, sum(total) as revenue, sum(seller_earning) as earnings')
            ->groupBy('seller_id', 'seller_name')->orderByDesc('revenue')->limit(10)->get();
        $byProvider = (clone $paidOrders)->selectRaw("coalesce(payment_provider, 'stripe') as provider, count(*) as orders, sum(total) as revenue")
            ->groupBy('provider')->orderByDesc('revenue')->get();
        $byLicense = (clone $paidItems)->selectRaw('license_name, count(*) as sales, sum(total) as revenue')
            ->groupBy('license_name')->orderByDesc('revenue')->get();

        return view('admin.reports.index', compact('summary', 'trend', 'trendMax', 'topProducts', 'topSellers', 'byProvider', 'byLicense', 'from', 'to'));
    }

    public function export(): StreamedResponse
    {
        [$from, $to] = $this->range();
        $items = OrderItem::with('order')->whereHas('order', fn (Builder $q) => $this->applyPaidFilter($q, $from, $to))->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($items): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order', 'Date', 'Product', 'Seller', 'License', 'Type', 'Gross', 'Commission', 'Seller earning', 'Provider', 'Currency']);
            foreach ($items as $item) {
                fputcsv($handle, [$item->order->number, $item->order->paid_at?->toDateString(), $item->product_title, $item->seller_name, $item->license_name, $item->item_type, $item->total, $item->platform_commission, $item->seller_earning, $item->order->payment_provider ?? 'stripe', $item->order->currency]);
            }
            fclose($handle);
        }, 'sales-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function range(): array
    {
        $from = rescue(fn () => Carbon::parse(request('from'))->startOfDay(), null, false);
        $to = rescue(fn () => Carbon::parse(request('to'))->endOfDay(), null, false);

        return [$from ?? now()->subDays(29)->startOfDay(), $to ?? now()->endOfDay()];
    }

    private function paidOrders(Carbon $from, Carbon $to): Builder
    {
        return $this->applyPaidFilter(Order::query(), $from, $to);
    }

    private function applyPaidFilter(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to]);
    }
}
