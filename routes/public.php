<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntryController::class, 'index'])->name('home');
Route::get('/entries/{slug}', [EntryController::class, 'show'])->name('entries.show');
Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
Route::get('/policies/{slug}', [PolicyController::class, 'show'])->name('policies.show');
Route::get('/money', [MemberController::class, 'index'])->name('members.index');
Route::get('/money/{slug}', [MemberController::class, 'show'])->name('members.show');
