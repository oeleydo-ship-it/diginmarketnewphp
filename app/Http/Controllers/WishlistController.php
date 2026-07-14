<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class WishlistController extends Controller
{
 public function index(): View{$wishlist=auth()->user()->wishlist()->firstOrCreate();$products=$wishlist->products()->published()->with(['seller.sellerProfile','category'])->paginate(18);return view('wishlist.index',compact('products'));}
 public function toggle(Product $product): RedirectResponse{abort_unless($product->status->value==='published',404);$wishlist=auth()->user()->wishlist()->firstOrCreate();$wishlist->products()->toggle($product->id);return back()->with('status','Wishlist updated.');}
}