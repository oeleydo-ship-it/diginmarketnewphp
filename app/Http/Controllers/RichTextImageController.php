<?php

namespace App\Http\Controllers;

use App\Enums\SellerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RichTextImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->hasRole('administrator') || $user->sellerProfile?->status === SellerStatus::Approved),
            403
        );

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $path = $data['image']->store('editor/'.now()->format('Y/m'), 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
