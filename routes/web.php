<?php

use App\SlideWire\DatabaseDocumentProvider;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentImageController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ThemeImageController;
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
    Route::post('/documents/{document}/images', [DocumentImageController::class, 'store'])->name('documents.images.store');
    Route::delete('/documents/{document}/images/{image}', [DocumentImageController::class, 'destroy'])->name('documents.images.destroy');

    Route::resource('themes', ThemeController::class);
    Route::patch('/themes/{theme}/restore', [ThemeController::class, 'restore'])->name('themes.restore');
    Route::post('/themes/{theme}/images', [ThemeImageController::class, 'store'])->name('themes.images.store');
    Route::delete('/themes/{theme}/images/{image}', [ThemeImageController::class, 'destroy'])->name('themes.images.destroy');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
