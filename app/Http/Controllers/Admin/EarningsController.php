<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\SellerWalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EarningsController extends Controller
{
    public function index(): View
    {
        $pending = WalletTransaction::with(['wallet.seller'])
            ->where('type', 'sale_credit')->whereNull('cleared_at')
            ->orderBy('available_at')
            ->paginate(20);
        $summary = [
            'total_pending' => (float) WalletTransaction::where('type', 'sale_credit')->whereNull('cleared_at')->sum('amount'),
            'entries' => WalletTransaction::where('type', 'sale_credit')->whereNull('cleared_at')->count(),
            'eligible_now' => WalletTransaction::where('type', 'sale_credit')->whereNull('cleared_at')->where('available_at', '<=', now())->count(),
            'clearance_days' => (int) config('marketplace.earnings_clearance_days', 14),
        ];

        return view('admin.earnings.index', compact('pending', 'summary'));
    }

    /** Force-release one pending earning after confirming the gateway settled the payment. */
    public function release(WalletTransaction $transaction, SellerWalletService $wallets): RedirectResponse
    {
        $wallets->release($transaction, auth()->user());

        return back()->with('status', '$'.number_format((float) $transaction->amount, 2).' released to '.($transaction->wallet?->seller?->name ?? 'seller').'\'s available balance.');
    }
}
