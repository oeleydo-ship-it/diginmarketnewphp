<?php
namespace App\Http\Controllers;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class SellerProductController extends Controller
{
 public function index(): View { $products=Product::where('seller_id',auth()->id())->latest()->paginate(15);return view('seller.products.index',compact('products')); }
 public function create(): View { $this->authorize('create',Product::class);$categories=Category::where('is_active',true)->orderBy('name')->get();return view('seller.products.create',compact('categories')); }
 public function store(StoreProductRequest $request): RedirectResponse { $data=$request->validated();$product=DB::transaction(function()use($data,$request){$product=Product::create(['seller_id'=>auth()->id(),'category_id'=>$data['category_id'],'title'=>$data['title'],'slug'=>str($data['title'])->slug().'-'.str()->lower(str()->random(6)),'short_description'=>$data['short_description'],'description'=>$data['description'],'regular_price'=>$data['regular_price'],'extended_price'=>$data['extended_price']??null,'status'=>ProductStatus::Draft]);$version=$product->versions()->create(['version_number'=>$data['version_number'],'release_title'=>$data['release_title'],'release_notes'=>$data['release_notes']??null,'status'=>ProductVersionStatus::Draft]);$file=$request->file('archive');$path=$file->store("products/{$product->id}/versions/{$version->id}",'local');$version->files()->create(['disk'=>'local','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType()?:'application/zip','extension'=>'zip','size'=>$file->getSize(),'checksum'=>hash_file('sha256',$file->getRealPath()),'scan_status'=>'pending']);return $product;});return redirect()->route('seller.products.index')->with('status','Product draft created.'); }
 public function submit(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('submit',$product);$service->submit($product,auth()->user());return back()->with('status','Product submitted for review.'); }
}