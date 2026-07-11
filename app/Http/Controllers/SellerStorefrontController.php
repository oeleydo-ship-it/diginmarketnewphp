<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use Illuminate\Contracts\View\View;
class SellerStorefrontController extends Controller {public function show(string $username): View{$seller=SellerProfile::with('user')->where('username',$username)->where('status',SellerStatus::Approved)->firstOrFail();$products=$seller->user->products()->published()->with('category')->latest('published_at')->paginate(12);$followers=$seller->user->followers()->count();return view('sellers.show',compact('seller','products','followers'));}}