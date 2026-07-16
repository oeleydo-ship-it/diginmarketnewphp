<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\SellerWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class SellerWalletService
{
    public function creditSale(OrderItem $item, string $currency): WalletTransaction
    {
        return DB::transaction(function () use ($item, $currency) {
            $reference = 'sale:'.$item->id;
            if ($existing = WalletTransaction::where('reference', $reference)->first()) {
                return $existing;
            }$wallet = SellerWallet::firstOrCreate(['seller_id' => $item->seller_id, 'currency' => $currency]);
            $wallet = SellerWallet::lockForUpdate()->findOrFail($wallet->id);
            $amount = (float) $item->seller_earning;
            $wallet->increment('pending_balance', $amount);
            $wallet->increment('lifetime_earnings', $amount);

            return $wallet->transactions()->create(['uuid' => (string) str()->uuid(), 'order_item_id' => $item->id, 'type' => 'sale_credit', 'balance_bucket' => 'pending', 'amount' => $amount, 'currency' => $currency, 'reference' => $reference, 'available_at' => now()->addDays((int) config('marketplace.earnings_clearance_days', 14)), 'metadata' => ['gross' => (string) $item->total, 'commission' => (string) $item->platform_commission], 'created_at' => now()]);
        });
    }

    public function clearEligible(): int
    {
        // Eligible when the stamped available_at has passed, OR the entry is older than the CURRENT
        // clearance window — so an admin lowering the setting releases already-pending earnings too
        // (raising it never delays funds that were promised sooner).
        $count = 0;
        WalletTransaction::where('type', 'sale_credit')->whereNull('cleared_at')->where(fn ($q) => $q->where('available_at', '<=', now())->orWhere('created_at', '<=', now()->subDays((int) config('marketplace.earnings_clearance_days', 14))))->orderBy('id')->chunkById(100, function ($entries) use (&$count) {
            foreach ($entries as $entry) {
                DB::transaction(function () use ($entry, &$count) {
                    $entry = WalletTransaction::lockForUpdate()->findOrFail($entry->id);
                    if ($entry->cleared_at) {
                        return;
                    }$wallet = SellerWallet::lockForUpdate()->findOrFail($entry->seller_wallet_id);
                    $amount = (float) $entry->amount;
                    $wallet->decrement('pending_balance', $amount);
                    $wallet->increment('available_balance', $amount);
                    $entry->update(['cleared_at' => now()]);
                    $wallet->transactions()->create(['uuid' => (string) str()->uuid(), 'order_item_id' => $entry->order_item_id, 'type' => 'clearance_credit', 'balance_bucket' => 'available', 'amount' => $amount, 'currency' => $entry->currency, 'reference' => 'clearance:'.$entry->id, 'metadata' => ['source_transaction' => $entry->uuid], 'created_at' => now()]);
                    $count++;
                });
            }
        });

        return $count;
    }
}
