<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
class CategoryController extends Controller { public function show(string $slug,ProductSearchService $search): View{$category=Category::where('slug',$slug)->where('is_active',true)->firstOrFail();$products=$search->search(array_merge(request()->all(),['category'=>$slug]));$categories=Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->orderBy('name')->get();return view('categories.show',compact('category','products','categories'));} }