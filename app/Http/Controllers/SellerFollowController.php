<?php
namespace App\Http\Controllers;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
class SellerFollowController extends Controller
{
    public function toggle(SellerProfile $sellerProfile): RedirectResponse
    {
        abort_if($sellerProfile->user_id === auth()->id(), 422);

        auth()->user()->followedSellers()->toggle($sellerProfile->user_id);

        // `back()` depends on the Referer header being present. In some real-world
        // clients that header is missing, which makes this redirect back to `/`,
        // making the follow action look like it "did nothing" to the user.
        return redirect()
            ->route('sellers.show', $sellerProfile->username)
            ->with('status', 'Following updated.');
    }
}