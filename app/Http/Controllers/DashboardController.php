<?php
namespace App\Http\Controllers;
use Illuminate\Contracts\View\View;
class DashboardController extends Controller
{
    public function __invoke(): View { $user = request()->user()->loadMissing('roles', 'sellerProfile'); return view('dashboard', compact('user')); }
}
