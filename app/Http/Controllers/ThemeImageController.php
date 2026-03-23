<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThemeImageController extends Controller
{
    public function store(Request $request, int $theme): RedirectResponse
    {
        $ownedTheme = $request->user()->themes()->findOrFail($theme);

        $attributes = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $file = $attributes['image'];
        $path = $file->store("themes/{$ownedTheme->id}", 'public');

        $ownedTheme->images()->create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        return redirect()->route('themes.edit', $ownedTheme)->with('status', 'Image uploaded successfully.');
    }

    public function destroy(Request $request, int $theme, int $image): RedirectResponse
    {
        $ownedTheme = $request->user()->themes()->findOrFail($theme);
        $ownedImage = $ownedTheme->images()->whereKey($image)->firstOrFail();

        Storage::disk('public')->delete($ownedImage->path);
        $ownedImage->delete();

        return redirect()->route('themes.edit', $ownedTheme)->with('status', 'Image removed successfully.');
    }
}
