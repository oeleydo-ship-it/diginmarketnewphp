<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(OrderItem $orderItem, ReviewService $service): RedirectResponse
    {
        abort_unless(Setting::enabled('features.reviews'), 403, 'Reviews are currently disabled.');
        $data = request()->validate(['rating' => ['required', 'integer', 'between:1,5'], 'title' => ['required', 'string', 'max:160'], 'content' => ['required', 'string', 'min:10', 'max:5000']]);
        $review = $service->create(auth()->user(), $orderItem->load('order', 'license'), $data);

        return back()->with('status', $review->wasRecentlyCreated ? 'Review published.' : 'Your review has been updated.');
    }
}
