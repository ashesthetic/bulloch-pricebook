<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::get('/pricebook/download', function () {
    abort_unless(auth()->user()?->hasRole(['super_admin', 'admin']), 403);

    $filePath = config('pricebook.path');
    if (! str_starts_with($filePath, '/')) {
        $filePath = base_path($filePath);
    }

    abort_if(empty($filePath) || ! is_file($filePath), 404);

    return response()->download($filePath, 'BT9000 Price Book.XML');
})->middleware('auth')->name('pricebook.download');

Route::get('/scan/{token}', [App\Http\Controllers\ScanController::class, 'show'])->name('scan.show');
Route::post('/scan/{token}', [App\Http\Controllers\ScanController::class, 'store'])->name('scan.store');
