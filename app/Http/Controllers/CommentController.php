<?php
namespace App\Http\Controllers;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
class CommentController extends Controller {public function store(Product $product):RedirectResponse{abort_unless($product->status->value==='published',404);$data=request()->validate(['content'=>['required','string','max:3000'],'parent_id'=>['nullable','exists:comments,id']]);if(isset($data['parent_id']))abort_unless(Comment::whereKey($data['parent_id'])->where('product_id',$product->id)->exists(),422);$product->comments()->create($data+['user_id'=>auth()->id(),'status'=>'approved']);return back()->with('status','Comment posted.');}}