<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Response;
class SitemapController extends Controller {public function __invoke(): Response{$urls=collect([route('home'),route('products.index')])->merge(Category::where('is_active',true)->pluck('slug')->map(fn($s)=>route('categories.show',$s)))->merge(Product::published()->pluck('slug')->map(fn($s)=>route('products.show',$s)))->merge(SellerProfile::where('status',SellerStatus::Approved)->pluck('username')->map(fn($u)=>route('sellers.show',$u)))->merge(Page::published()->pluck('slug')->map(fn($s)=>route('pages.show',$s)));$xml=view('seo.sitemap',compact('urls'))->render();return response($xml,200,['Content-Type'=>'application/xml']);}}