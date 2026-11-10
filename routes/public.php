<?php

use App\Http\Controllers\EntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntryController::class, 'index'])->name('home');
Route::get('/entries/{slug}', [EntryController::class, 'show'])->name('entries.show');
