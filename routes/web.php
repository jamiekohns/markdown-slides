<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentImageController;
use App\Http\Controllers\DocumentScriptController;
use App\Http\Controllers\DocumentSlideController;
use App\Http\Controllers\PresentationPresenterController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ThemeImageController;
use App\Http\Controllers\UserController;
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('presentations', DocumentController::class);
    Route::patch('/presentations/{document}/restore', [DocumentController::class, 'restore'])->name('presentations.restore');
    Route::post('/presentations/{document}/images', [DocumentImageController::class, 'store'])->name('presentations.images.store');
    Route::delete('/presentations/{document}/images/{image}', [DocumentImageController::class, 'destroy'])->name('presentations.images.destroy');
    Route::get('/presentations/{document}/slides', [DocumentSlideController::class, 'index'])->name('presentations.slides.index');
    Route::post('/presentations/{document}/slides', [DocumentSlideController::class, 'store'])->name('presentations.slides.store');
    Route::put('/presentations/{document}/slides/{slide}', [DocumentSlideController::class, 'update'])->name('presentations.slides.update');
    Route::delete('/presentations/{document}/slides/{slide}', [DocumentSlideController::class, 'destroy'])->name('presentations.slides.destroy');
    Route::post('/presentations/{document}/slides/reorder', [DocumentSlideController::class, 'reorder'])->name('presentations.slides.reorder');
    Route::post('/presentations/{document}/slides/save-all', [DocumentSlideController::class, 'saveAll'])->name('presentations.slides.save-all');
    Route::get('/presentations/{document}/slides/export', [DocumentSlideController::class, 'export'])->name('presentations.slides.export');
    Route::post('/presentations/{document}/slides/import', [DocumentSlideController::class, 'import'])->name('presentations.slides.import');
    Route::get('/presentations/{document}/script', [DocumentScriptController::class, 'show'])->name('presentations.script.show');
    Route::put('/presentations/{document}/script', [DocumentScriptController::class, 'update'])->name('presentations.script.update');
    Route::get('/presentations/{document}/presenter', PresentationPresenterController::class)->name('presentations.presenter.show');

    Route::resource('themes', ThemeController::class);
    Route::patch('/themes/{theme}/restore', [ThemeController::class, 'restore'])->name('themes.restore');
    Route::post('/themes/{theme}/images', [ThemeImageController::class, 'store'])->name('themes.images.store');
    Route::delete('/themes/{theme}/images/{image}', [ThemeImageController::class, 'destroy'])->name('themes.images.destroy');

    Route::resource('users', UserController::class)->except(['show']);

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::get('/{slug}', PresentationController::class)->name('public.presentations.show');
