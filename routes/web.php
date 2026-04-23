<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::get('/scan/{token}', [App\Http\Controllers\ScanController::class, 'show'])->name('scan.show');
Route::post('/scan/{token}', [App\Http\Controllers\ScanController::class, 'store'])->name('scan.store');
