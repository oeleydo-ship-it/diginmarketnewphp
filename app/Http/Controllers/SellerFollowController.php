<?php
namespace App\Http\Controllers;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
class SellerFollowController extends Controller {public function toggle(SellerProfile $sellerProfile): RedirectResponse{abort_if($sellerProfile->user_id===auth()->id(),422);auth()->user()->followedSellers()->toggle($sellerProfile->user_id);return back()->with('status','Following updated.');}}