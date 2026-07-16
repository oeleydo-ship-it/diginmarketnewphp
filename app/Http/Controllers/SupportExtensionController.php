<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Services\DirectCheckoutService;
use Illuminate\Http\RedirectResponse;

class SupportExtensionController extends Controller
{
    public function buy(License $license, DirectCheckoutService $checkout): RedirectResponse
    {
        $result = $checkout->startSupportExtension($license, auth()->user(), request('payment_provider'));

        return redirect()->away($result['url']);
    }
}
