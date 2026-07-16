<?php
namespace App\Http\Controllers;
use Illuminate\Contracts\View\View;
class SellerFinanceController extends Controller {public function __invoke():View{$wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);$transactions=$wallet->transactions()->latest('id')->paginate(30);$withdrawals=auth()->user()->withdrawals()->latest()->limit(10)->get();$connected=auth()->user()->stripeConnectedAccount;$profile=auth()->user()->sellerProfile;return view('seller.finance',compact('wallet','transactions','withdrawals','connected','profile'));}}