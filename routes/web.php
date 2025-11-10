<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConvertController;
Route::get('/', function () {
    return redirect()->route('convert.index');
});
Route::get('/convert', [ConvertController::class, 'index'])->name('convert.index');
Route::post('/convert', [ConvertController::class, 'convert'])->name('convert.process');
