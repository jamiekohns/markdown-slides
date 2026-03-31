<?php

use App\SlideWire\DatabaseDocumentProvider;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentImageController;
use App\Http\Controllers\DocumentSlideController;
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
    Route::get('/documents/{document}/slides', [DocumentSlideController::class, 'index'])->name('documents.slides.index');
    Route::post('/documents/{document}/slides', [DocumentSlideController::class, 'store'])->name('documents.slides.store');
    Route::put('/documents/{document}/slides/{slide}', [DocumentSlideController::class, 'update'])->name('documents.slides.update');
    Route::delete('/documents/{document}/slides/{slide}', [DocumentSlideController::class, 'destroy'])->name('documents.slides.destroy');
    Route::post('/documents/{document}/slides/reorder', [DocumentSlideController::class, 'reorder'])->name('documents.slides.reorder');
    Route::post('/documents/{document}/slides/save-all', [DocumentSlideController::class, 'saveAll'])->name('documents.slides.save-all');
    Route::get('/documents/{document}/slides/export', [DocumentSlideController::class, 'export'])->name('documents.slides.export');
    Route::post('/documents/{document}/slides/import', [DocumentSlideController::class, 'import'])->name('documents.slides.import');

    Route::resource('themes', ThemeController::class);
    Route::patch('/themes/{theme}/restore', [ThemeController::class, 'restore'])->name('themes.restore');
    Route::post('/themes/{theme}/images', [ThemeImageController::class, 'store'])->name('themes.images.store');
    Route::delete('/themes/{theme}/images/{image}', [ThemeImageController::class, 'destroy'])->name('themes.images.destroy');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
