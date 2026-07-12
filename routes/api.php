<?php
use App\Http\Controllers\Api\LicenseApiController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1/licenses')->middleware('throttle:license-api')->group(function () {
    Route::post('/verify', [LicenseApiController::class, 'verify'])->name('api.licenses.verify');
    Route::post('/activate', [LicenseApiController::class, 'activate'])->name('api.licenses.activate');
    Route::post('/deactivate', [LicenseApiController::class, 'deactivate'])->name('api.licenses.deactivate');
    Route::post('/update-check', [LicenseApiController::class, 'updateCheck'])->name('api.licenses.update-check');
});
