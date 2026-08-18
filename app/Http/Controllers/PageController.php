<?php
namespace App\Http\Controllers;
use App\Models\Page;
use Illuminate\Contracts\View\View;
class PageController extends Controller
{
 public function show(string $slug): View
 {
  abort_if($slug===Page::HOMEPAGE_SLUG,404);
  $page=Page::published()->where('slug',$slug)->firstOrFail();
  return view('pages.show',compact('page'));
 }
}
