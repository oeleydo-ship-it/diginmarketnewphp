<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\LicenseType;
use App\Models\Product;
use App\Services\CartPricingService;
use App\Services\CouponService;
use App\Services\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(CartPricingService $pricing, PaymentGatewayManager $gateways): View
    {
        $cart = auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD']);
        $totals = $pricing->reprice($cart);
        $cart->load('items.product.seller', 'items.product.category', 'items.licenseType', 'coupon.seller');
        $methods = $gateways->availableFor($cart->currency);

        return view('cart.index', compact('cart', 'totals', 'methods'));
    }

    public function add(Product $product, CartPricingService $pricing): RedirectResponse
    {
        abort_unless($product->status->value === 'published', 404);
        abort_if($product->seller_id === auth()->id(), 422, 'You cannot purchase your own product.');
        $data = request()->validate(['license_type_id' => ['required', 'exists:license_types,id']]);
        $license = LicenseType::where('is_active', true)->findOrFail($data['license_type_id']);
        abort_if($license->slug === 'extended' && ! $product->business_license_enabled, 422, 'The business license is not offered for this product.');
        $cart = auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD']);
        $price = $pricing->unitPrice($product, $license);
        $cart->items()->updateOrCreate(['product_id' => $product->id], ['license_type_id' => $license->id, 'unit_price' => $price, 'tax' => 0, 'discount' => 0, 'total' => $price]);

        return redirect()->route('cart.index')->with('status', 'Product added to cart.');
    }

    public function remove(int $item): RedirectResponse
    {
        $cart = auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD']);
        $cart->items()->whereKey($item)->delete();

        return back();
    }

    public function applyCoupon(CouponService $coupons, CartPricingService $pricing): RedirectResponse
    {
        $data = request()->validate(['code' => ['required', 'string', 'max:40']]);
        $cart = auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD']);
        $coupon = Coupon::whereRaw('upper(code) = ?', [strtoupper(trim($data['code']))])->first();
        if (! $coupon) {
            throw ValidationException::withMessages(['coupon' => 'That coupon code is not valid.']);
        }
        $pricing->reprice($cart);
        $coupons->validate($coupon, auth()->user(), $pricing->couponEligibleSubtotal($cart, $coupon));
        $cart->update(['coupon_id' => $coupon->id]);

        return redirect()->route('cart.index')->with('status', 'Coupon '.$coupon->code.' applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        auth()->user()->cart()->firstOrCreate([], ['currency' => 'USD'])->update(['coupon_id' => null]);

        return redirect()->route('cart.index')->with('status','Coupon removed.');
    }
}
