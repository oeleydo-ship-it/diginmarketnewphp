<?php

namespace App\Jobs;

use App\Contracts\FileScanner;
use App\Models\ProductFile;
use App\Services\AdminNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ScanProductFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $productFileId) {}

    public function handle(FileScanner $scanner): void
    {
        $file = ProductFile::find($this->productFileId);
        if (! $file || $file->scan_status !== 'pending') {
            return;
        }
        $path = Storage::disk($file->disk)->path($file->path);
        $status = is_file($path) ? $scanner->scan($path) : 'error';
        $file->update(['scan_status' => $status]);
        if ($status !== 'clean') {
            app(AdminNotifier::class)->notify('review', 'Upload flagged '.$status.' by the file scanner: '.$file->original_name, route('admin.products.review'));
        }
    }
}
