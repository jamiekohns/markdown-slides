<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $themes = $request->user()->themes()->latest()->get();
        $deletedThemes = $request->user()->themes()->onlyTrashed()->latest('deleted_at')->get();

        return view('themes.index', [
            'themes' => $themes,
            'deletedThemes' => $deletedThemes,
        ]);
    }

    public function create(): View
    {
        return view('themes.create', [
            'images' => collect(),
            'canUploadImages' => false,
            'uploadImageRoute' => null,
            'deleteImageRouteName' => null,
            'imageOwnerId' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'css' => ['required', 'string'],
        ]);

        $theme = $request->user()->themes()->create($attributes);

        return redirect()->route('themes.show', $theme)->with('status', 'Theme created successfully.');
    }

    public function show(Request $request, int $theme): View
    {
        $ownedTheme = $request->user()->themes()->findOrFail($theme);

        return view('themes.show', ['theme' => $ownedTheme]);
    }

    public function edit(Request $request, int $theme): View
    {
        $ownedTheme = $request->user()->themes()->with('images')->findOrFail($theme);

        return view('themes.edit', [
            'theme' => $ownedTheme,
            'images' => $ownedTheme->images,
            'canUploadImages' => true,
            'uploadImageRoute' => route('themes.images.store', $ownedTheme),
            'deleteImageRouteName' => 'themes.images.destroy',
            'imageOwnerId' => $ownedTheme->id,
        ]);
    }

    public function update(Request $request, int $theme): RedirectResponse
    {
        $ownedTheme = $request->user()->themes()->findOrFail($theme);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'css' => ['required', 'string'],
        ]);

        $ownedTheme->update($attributes);

        return redirect()->route('themes.show', $ownedTheme)->with('status', 'Theme updated successfully.');
    }

    public function destroy(Request $request, int $theme): RedirectResponse
    {
        $ownedTheme = $request->user()->themes()->findOrFail($theme);

        $ownedTheme->delete();

        return redirect()->route('themes.index')->with('status', 'Theme moved to trash.');
    }

    public function restore(Request $request, int $theme): RedirectResponse
    {
        $ownedTheme = $request->user()->themes()->onlyTrashed()->findOrFail($theme);

        $ownedTheme->restore();

        return redirect()->route('themes.index')->with('status', 'Theme restored.');
    }
}
