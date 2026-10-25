<?php

namespace App\Services;

use App\Mail\SellerNoticeMail;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\SellerProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Bell notifications and emails telling sellers where their application and listings stand.
 * Each event group can be switched off per channel under Admin → Settings → Notifications.
 */
class SellerNotifier
{
    public function __construct(private UserNotifier $users) {}

    public function applicationApproved(SellerProfile $profile): void
    {
        $url = route('seller.products.index');
        $this->send($profile->user, 'seller_application', 'seller', 'Your seller application was approved — you can start listing products', $url,
            new SellerNoticeMail('Your seller application was approved', 'Welcome aboard, '.$profile->display_name.'! You can now upload products and submit them for review.', $url, 'Start listing products'));
    }

    public function applicationRejected(SellerProfile $profile): void
    {
        $url = route('seller.apply');
        $this->send($profile->user, 'seller_application', 'seller', 'Your seller application was not approved', $url,
            new SellerNoticeMail('Your seller application was not approved', 'Thanks for applying to sell on '.config('app.name').'. After review we could not approve your application this time.', $url, 'View your application', (string) $profile->rejection_reason));
    }

    public function productSubmitted(Product $product): void
    {
        $this->forProduct($product, 'product_submitted', '“'.$product->title.'” was submitted for review',
            'Submitted for review: '.$product->title, 'We received **'.$product->title.'** and our team will review it shortly. You will hear from us as soon as there is a decision.');
    }

    public function versionSubmitted(ProductVersion $version): void
    {
        $product = $version->product;
        $this->forProduct($product, 'product_submitted', 'Version '.$version->version_number.' of “'.$product->title.'” was submitted for review',
            'Update submitted for review: '.$product->title.' v'.$version->version_number, 'We received version **'.$version->version_number.'** of **'.$product->title.'**. Buyers will get it once it passes review.');
    }

    /** The approval email itself is ProductReviewOutcomeMail, sent by MarketplaceMailer. */
    public function productApproved(Product $product): void
    {
        $this->forProduct($product, 'product_reviewed', '“'.$product->title.'” was approved and is now live');
    }

    /** The changes-requested email itself is ProductReviewOutcomeMail, sent by MarketplaceMailer. */
    public function productChangesRequested(Product $product): void
    {
        $this->forProduct($product, 'product_reviewed', 'Changes requested on “'.$product->title.'”');
    }

    public function versionApproved(ProductVersion $version): void
    {
        $product = $version->product;
        $this->forProduct($product, 'product_reviewed', 'Version '.$version->version_number.' of “'.$product->title.'” was approved',
            'Update approved: '.$product->title.' v'.$version->version_number, 'Version **'.$version->version_number.'** of **'.$product->title.'** passed review and is now available to buyers.');
    }

    public function versionRejected(ProductVersion $version, string $notes = ''): void
    {
        $product = $version->product;
        $this->forProduct($product, 'product_reviewed', 'Version '.$version->version_number.' of “'.$product->title.'” was rejected',
            'Update rejected: '.$product->title.' v'.$version->version_number, 'Version **'.$version->version_number.'** of **'.$product->title.'** did not pass review. Please address the notes below and upload a new version.', $notes);
    }

    private function forProduct(Product $product, string $event, string $title, ?string $subject = null, string $body = '', string $notes = ''): void
    {
        $product->loadMissing('seller');
        if (! $product->seller) {
            return;
        }
        $url = route('seller.products.edit', $product);
        $this->send($product->seller, $event, 'review', $title, $url,
            $subject === null ? null : new SellerNoticeMail($subject, $body, $url, 'Open your product', $notes));
    }

    private function send(User $user, string $event, string $kind, string $title, string $url, ?SellerNoticeMail $mail): void
    {
        if (Setting::enabled('notifications.'.$event.'.app')) {
            $this->users->notify($user, $kind, $title, $url);
        }
        if ($mail && Setting::enabled('notifications.'.$event.'.mail')) {
            rescue(fn () => Mail::to($user)->queue($mail), report: true);
        }
    }
}
