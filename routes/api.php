<?php

use App\Http\Controllers\Api\BukuController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('buku', [BukuController::class, 'index']);
    Route::get('buku/{buku}', [BukuController::class, 'show']);

    Route::middleware(['auth', 'can:kelola-buku'])->group(function () {
        Route::post('buku', [BukuController::class, 'store']);
        Route::match(['put', 'patch'], 'buku/{buku}', [BukuController::class, 'update']);
        Route::delete('buku/{buku}', [BukuController::class, 'destroy']);
    });
});
