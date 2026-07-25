<?php

namespace App\Jobs;

use App\Mail\ProductUpdateMail;
use App\Models\License;
use App\Models\NotificationPreference;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Tell everyone holding an active licence for a product that a newer version was published.
 * Chunked and de-duplicated by user, so a buyer who owns several licences for the same product
 * gets one mail; honours the email_product_updates preference.
 */
class NotifyBuyersOfProductUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $versionId) {}

    public function handle(): void
    {
        $version = ProductVersion::with('product')->find($this->versionId);
        if (! $version || $version->status->value !== 'published' || ! $version->product) {
            return;
        }
        $notified = [];
        License::query()->where('product_id', $version->product_id)->where('status', 'active')->where('product_version_id', '!=', $version->id)->with('orderItem.order')->chunkById(200, function ($licenses) use ($version, &$notified) {
            foreach ($licenses as $license) {
                if (isset($notified[$license->user_id]) || ! $license->orderItem?->order?->isSettled()) {
                    continue;
                }
                $buyer = User::find($license->user_id);
                if (! $buyer || ! $this->wantsUpdates($buyer)) {
                    continue;
                }
                $notified[$license->user_id] = true;
                Mail::to($buyer)->queue(new ProductUpdateMail($version, route('purchases.show', $license->orderItem->order_id)));
            }
        });
    }

    private function wantsUpdates(User $user): bool
    {
        $preference = NotificationPreference::where('user_id', $user->id)->first();

        return $preference === null || (bool) $preference->email_product_updates;
    }
}
