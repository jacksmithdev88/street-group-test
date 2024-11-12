<?php

use App\Http\Controllers\HomeownerController;

Route::get('/', [HomeownerController::class, 'showForm'])->name('upload.form');
Route::post('/', [HomeownerController::class, 'upload'])->name('upload.process');
