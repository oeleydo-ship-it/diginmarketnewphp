<?php
namespace App\Http\Controllers;
use App\Enums\ProductStatus;
use App\Enums\ProductVersionStatus;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreVersionRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductSubmissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class SellerProductController extends Controller
{
 public function index(): View { $products=Product::where('seller_id',auth()->id())->withCount('versions')->latest()->paginate(15);return view('seller.products.index',compact('products')); }
 public function create(): View { $this->authorize('create',Product::class);$categories=Category::where('is_active',true)->orderBy('name')->get();return view('seller.products.create',compact('categories')); }
 public function store(StoreProductRequest $request): RedirectResponse { $data=$request->validated();$product=DB::transaction(function()use($data,$request){$product=Product::create(['seller_id'=>auth()->id(),'category_id'=>$data['category_id'],'title'=>$data['title'],'slug'=>str($data['title'])->slug().'-'.str()->lower(str()->random(6)),'short_description'=>$data['short_description'],'description'=>$data['description'],'regular_price'=>$data['regular_price'],'extended_price'=>$data['extended_price']??null,'business_license_enabled'=>$request->boolean('business_license_enabled'),'demo_url'=>$data['demo_url']??null,'video_url'=>$data['video_url']??null,'status'=>ProductStatus::Draft]);$version=$product->versions()->create(['version_number'=>$data['version_number'],'release_title'=>$data['release_title'],'release_notes'=>$data['release_notes']??null,'status'=>ProductVersionStatus::Draft]);$file=$request->file('archive');$path=$file->store("products/{$product->id}/versions/{$version->id}",'local');$version->files()->create(['disk'=>'local','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType()?:'application/zip','extension'=>'zip','size'=>$file->getSize(),'checksum'=>hash_file('sha256',$file->getRealPath()),'scan_status'=>'pending']);$this->attachImages($request,$product);$this->attachImageUrls($data['image_urls']??null,$product);return $product;});return redirect()->route('seller.products.index')->with('status','Product draft created.'); }
 public function edit(Product $product): View { $this->authorize('editAny',$product);$categories=Category::where('is_active',true)->orderBy('name')->get();$versions=$product->versions()->latest()->get();$product->load('images');return view('seller.products.edit',compact('product','categories','versions')); }
 public function update(UpdateProductRequest $request,Product $product): RedirectResponse { $product->update($request->validated()+['business_license_enabled'=>$request->boolean('business_license_enabled')]);return back()->with('status','Product details updated.'); }
 public function storeImages(Product $product): RedirectResponse
 {
  $this->authorize('editAny',$product);
  $data=request()->validate(['images'=>['nullable','required_without:image_urls','array','max:6'],'images.*'=>['image','mimes:jpg,jpeg,png,webp','max:5120'],'image_urls'=>['nullable','required_without:images','string','max:2000']]);
  abort_if($product->images()->count()+count(request()->file('images')??[])>6,422,'A product can have at most 6 images.');
  $this->attachImages(request(),$product);
  $this->attachImageUrls($data['image_urls']??null,$product);
  return back()->with('status','Images added.');
 }
 public function destroyImage(Product $product,\App\Models\ProductImage $image): RedirectResponse
 {
  $this->authorize('editAny',$product);
  abort_unless($image->product_id===$product->id,404);
  if($image->disk!=='external')Storage::disk($image->disk)->delete($image->path);
  $image->delete();
  $product->refreshCoverImage();
  return back()->with('status','Image removed.');
 }
 private function attachImages(\Illuminate\Http\Request $request,Product $product): void
 {
  if(!$request->hasFile('images'))return;
  $sort=(int)$product->images()->max('sort_order');
  foreach($request->file('images') as $file){$path=$file->store("products/{$product->id}/images",'public');$product->images()->create(['disk'=>'public','path'=>$path,'original_name'=>$file->getClientOriginalName(),'sort_order'=>++$sort]);}
  $product->refreshCoverImage();
 }
 /** One https image URL per line; hotlinked rather than downloaded, stored with disk 'external'. */
 private function attachImageUrls(?string $raw,Product $product): void
 {
  $urls=collect(preg_split('/\r\n|\r|\n/',(string)$raw))->map(fn($u)=>trim($u))->filter()->unique()->values();
  if($urls->isEmpty())return;
  foreach($urls as $url)if(!preg_match('~^https://\S+$~i',$url))throw \Illuminate\Validation\ValidationException::withMessages(['image_urls'=>'Each image URL must be a valid https:// link ('.str($url)->limit(60).' is not).']);
  abort_if($product->images()->count()+$urls->count()>6,422,'A product can have at most 6 images.');
  $sort=(int)$product->images()->max('sort_order');
  foreach($urls as $url)$product->images()->create(['disk'=>'external','path'=>$url,'sort_order'=>++$sort]);
  $product->refreshCoverImage();
 }
 public function storeVersion(StoreVersionRequest $request,Product $product): RedirectResponse
 {
  $data=$request->validated();
  if($product->versions()->where('status',ProductVersionStatus::PendingReview)->exists())return back()->withErrors(['version_number'=>'A version is already awaiting review.']);
  DB::transaction(function()use($data,$request,$product){$version=$product->versions()->create(['version_number'=>$data['version_number'],'release_title'=>$data['release_title'],'release_notes'=>$data['release_notes']??null,'status'=>ProductVersionStatus::PendingReview]);$file=$request->file('archive');$path=$file->store("products/{$product->id}/versions/{$version->id}",'local');$version->files()->create(['disk'=>'local','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType()?:'application/zip','extension'=>'zip','size'=>$file->getSize(),'checksum'=>hash_file('sha256',$file->getRealPath()),'scan_status'=>'pending']);});
  return back()->with('status','Version update submitted for review.');
 }
 public function submit(Product $product,ProductSubmissionService $service): RedirectResponse { $this->authorize('submit',$product);$service->submit($product,auth()->user());return back()->with('status','Product submitted for review.'); }
}
