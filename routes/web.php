<?php

use App\SlideWire\DatabaseDocumentProvider;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::slidewire('/presentations', DatabaseDocumentProvider::class);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('documents', DocumentController::class);
    Route::patch('/documents/{document}/restore', [DocumentController::class, 'restore'])->name('documents.restore');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
