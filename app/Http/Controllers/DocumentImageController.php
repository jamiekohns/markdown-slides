<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentImageController extends Controller
{
    public function store(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        $attributes = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $file = $attributes['image'];
        $path = $file->store("documents/{$ownedDocument->id}", 'public');

        $ownedDocument->images()->create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        return redirect()->route('presentations.edit', $ownedDocument)->with('status', 'Image uploaded successfully.');
    }

    public function destroy(Request $request, int $document, int $image): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);
        $ownedImage = $ownedDocument->images()->whereKey($image)->firstOrFail();

        Storage::disk('public')->delete($ownedImage->path);
        $ownedImage->delete();

        return redirect()->route('presentations.edit', $ownedDocument)->with('status', 'Image removed successfully.');
    }
}
