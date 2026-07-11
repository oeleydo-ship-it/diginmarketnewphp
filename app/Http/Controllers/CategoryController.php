<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
class CategoryController extends Controller { public function show(string $slug,ProductSearchService $search): View{$category=Category::where('slug',$slug)->where('is_active',true)->firstOrFail();$products=$search->search(request()->all()+['category'=>$slug]);return view('categories.show',compact('category','products'));} }