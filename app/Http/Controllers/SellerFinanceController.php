<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class SellerFinanceController extends Controller
{
    public function __invoke(): View
    {
        $wallet = auth()->user()->sellerWallets()->where('currency', 'USD')->firstOrCreate(['currency' => 'USD']);
        $transactions = $wallet->transactions()->latest('id')->paginate(30);
        $withdrawals = auth()->user()->withdrawals()->latest()->limit(10)->get();
        $connected = auth()->user()->stripeConnectedAccount;
        $profile = auth()->user()->sellerProfile;
        // When nothing is withdrawable yet, tell the seller when their earliest pending earning clears.
        $nextClearance = $wallet->transactions()->where('type', 'sale_credit')->whereNull('cleared_at')->whereNotNull('available_at')->min('available_at');
        $nextClearance = $nextClearance ? Carbon::parse($nextClearance) : null;

        return view('seller.finance', compact('wallet', 'transactions', 'withdrawals', 'connected', 'profile', 'nextClearance'));
    }
}
