<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class DisputeReviewController extends Controller
{
 public function index(): View {$disputes=Dispute::with(['user','orderItem'])->whereIn('status',['open','under_review'])->oldest()->paginate(20);return view('admin.disputes.index',compact('disputes'));}
 public function uphold(Dispute $dispute,DisputeService $service):RedirectResponse{$data=request()->validate(['amount'=>['required','numeric','min:0.01'],'decision'=>['nullable','string','max:2000']]);$service->uphold($dispute->load('orderItem.order','orderItem.license'),auth()->user(),(float)$data['amount'],(string)($data['decision']??''));return back()->with('status','Dispute upheld; fulfilment revoked and seller ledger adjusted.');}
 public function dismiss(Dispute $dispute,DisputeService $service):RedirectResponse{$service->dismiss($dispute->load('orderItem.license'),auth()->user(),(string)request('decision'));return back()->with('status','Dispute dismissed and license reinstated.');}
}
