<?php
namespace App\Http\Controllers;
use App\Models\LicenseType;
use App\Models\Product;
use App\Services\CartPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class CartController extends Controller
{
 public function index(CartPricingService $pricing): View{$cart=auth()->user()->cart()->firstOrCreate([],['currency'=>'USD']);$totals=$pricing->reprice($cart);$cart->load('items.product.seller','items.licenseType');return view('cart.index',compact('cart','totals'));}
 public function add(Product $product,CartPricingService $pricing): RedirectResponse{abort_unless($product->status->value==='published',404);$data=request()->validate(['license_type_id'=>['required','exists:license_types,id']]);$license=LicenseType::where('is_active',true)->findOrFail($data['license_type_id']);$cart=auth()->user()->cart()->firstOrCreate([],['currency'=>'USD']);$price=$pricing->unitPrice($product,$license);$cart->items()->updateOrCreate(['product_id'=>$product->id],['license_type_id'=>$license->id,'unit_price'=>$price,'tax'=>0,'discount'=>0,'total'=>$price]);return redirect()->route('cart.index')->with('status','Product added to cart.');}
 public function remove(int $item): RedirectResponse{$cart=auth()->user()->cart()->firstOrCreate([],['currency'=>'USD']);$cart->items()->whereKey($item)->delete();return back();}
}