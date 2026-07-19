<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentWebhookEvent;
use App\Models\ProductFile;
use App\Models\WalletTransaction;
use App\Services\ApplicationUpdateService;
use App\Services\SellerWalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index(): View
    {
        $health = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'mail_mailer' => config('mail.default'),
            'pending_jobs' => (int) DB::table('jobs')->count(),
            'failed_jobs' => (int) DB::table('failed_jobs')->count(),
            'webhook_events' => PaymentWebhookEvent::count(),
            'last_webhook_at' => PaymentWebhookEvent::max('created_at'),
            'last_clearance_at' => WalletTransaction::where('type', 'clearance_credit')->max('created_at'),
            'product_files' => ProductFile::count(),
            'product_storage_bytes' => (int) ProductFile::sum('size'),
            'storage_free_bytes' => @disk_free_space(storage_path()) ?: 0,
        ];
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->limit(10)->get();
        $backups = collect(glob($this->backupDir().DIRECTORY_SEPARATOR.'backup-*.zip'))->sortDesc()->take(10)->map(fn ($file) => ['name' => basename($file), 'size' => filesize($file), 'created_at' => Carbon::createFromTimestamp(filemtime($file))])->values();

        return view('admin.system.index', compact('health', 'failedJobs', 'backups'));
    }

    public function backup(): RedirectResponse
    {
        Artisan::call('marketplace:backup');
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'system.backup_run', 'entity_type' => null, 'entity_id' => null, 'new_values' => ['output' => trim(Artisan::output())], 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

        return back()->with('status', 'Backup created.');
    }

    /** Apply an uploaded release zip: validated, versioned, backed up, migrated, audited. */
    public function update(ApplicationUpdateService $updater): RedirectResponse
    {
        request()->validate(['package' => ['required', 'file', 'mimes:zip', 'max:262144']]);
        try {
            $result = $updater->apply(request()->file('package'), auth()->user(), request()->boolean('allow_downgrade'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['package' => $e->getMessage()]);
        }

        return back()->with('status', 'Updated '.$result['previous'].' → '.$result['version'].' ('.$result['files'].' files). Caches cleared and migrations run.');
    }

    /** Run the earnings clearance on demand instead of waiting for the 01:00 scheduler. */
    public function clearEarnings(SellerWalletService $wallets): RedirectResponse
    {
        $count = $wallets->clearEligible();
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'system.clearance_run', 'entity_type' => null, 'entity_id' => null, 'new_values' => ['cleared' => $count], 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

        return back()->with('status', $count.' earning'.($count === 1 ? '' : 's').' cleared to available balances.');
    }

    private function backupDir(): string
    {
        return config('marketplace.backup_path') ?: storage_path('app/backups');
    }
}
