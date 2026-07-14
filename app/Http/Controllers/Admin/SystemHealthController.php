<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ProductFile;
use App\Models\StripeWebhookEvent;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
class SystemHealthController extends Controller
{
 public function __invoke(): View
 {
  $health=[
   'php_version'=>PHP_VERSION,
   'laravel_version'=>app()->version(),
   'environment'=>app()->environment(),
   'debug'=>config('app.debug'),
   'queue_driver'=>config('queue.default'),
   'cache_driver'=>config('cache.default'),
   'mail_mailer'=>config('mail.default'),
   'pending_jobs'=>(int)DB::table('jobs')->count(),
   'failed_jobs'=>(int)DB::table('failed_jobs')->count(),
   'webhook_events'=>StripeWebhookEvent::count(),
   'last_webhook_at'=>StripeWebhookEvent::max('created_at'),
   'last_clearance_at'=>WalletTransaction::where('type','clearance_credit')->max('created_at'),
   'product_files'=>ProductFile::count(),
   'product_storage_bytes'=>(int)ProductFile::sum('size'),
   'storage_free_bytes'=>@disk_free_space(storage_path())?:0,
  ];
  $failedJobs=DB::table('failed_jobs')->latest('failed_at')->limit(10)->get();
  return view('admin.system.index',compact('health','failedJobs'));
 }
}
