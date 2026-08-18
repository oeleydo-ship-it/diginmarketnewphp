<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Response;
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([url(route('home')), url(route('products.index'))])
            ->merge(
                Category::where('is_active', true)
                    ->pluck('slug')
                    ->map(fn ($s) => url(route('categories.show', $s)))
            )
            ->merge(
                Product::published()
                    ->pluck('slug')
                    ->map(fn ($s) => url(route('products.show', $s)))
            )
            ->merge(
                SellerProfile::where('status', SellerStatus::Approved)
                    ->pluck('username')
                    ->map(fn ($u) => url(route('sellers.show', $u)))
            )
            ->merge(
                Page::published()
                    ->where('slug', '!=', Page::HOMEPAGE_SLUG)
                    ->pluck('slug')
                    ->map(fn ($s) => url(route('pages.show', $s)))
            )
            ->merge(
                \App\Models\BlogPost::published()
                    ->pluck('slug')
                    ->map(fn ($s) => url(route('blog.show', $s)))
            );

        $xml = view('seo.sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}