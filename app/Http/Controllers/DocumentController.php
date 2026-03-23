<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $documents = $request->user()->documents()->with('theme')->latest()->get();
        $deletedDocuments = $request->user()->documents()->with('theme')->onlyTrashed()->latest('deleted_at')->get();

        return view('documents.index', [
            'documents' => $documents,
            'deletedDocuments' => $deletedDocuments,
        ]);
    }

    public function create(Request $request): View
    {
        return view('documents.create', [
            'themes' => $request->user()->themes()->latest('name')->get(),
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'theme_id' => [
                'nullable',
                'integer',
                Rule::exists('themes', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ]);

        $document = $request->user()->documents()->create($attributes);

        $document->slides()->create([
            'sort_order' => 1,
            'content' => "# New slide\n\nStart writing your presentation content.",
        ]);

        return redirect()->route('documents.edit', $document)->with('status', 'Presentation created successfully.');
    }

    public function show(Request $request, int $document): View
    {
        $ownedDocument = $request->user()->documents()->with(['theme', 'slides'])->findOrFail($document);

        return view('documents.show', ['document' => $ownedDocument]);
    }

    public function edit(Request $request, int $document): View
    {
        $ownedDocument = $request->user()->documents()->with(['theme', 'images', 'slides'])->findOrFail($document);

        return view('documents.edit', [
            'document' => $ownedDocument,
            'themes' => $request->user()->themes()->latest('name')->get(),
            'images' => $ownedDocument->images,
            'canUploadImages' => true,
            'uploadImageRoute' => route('documents.images.store', $ownedDocument),
            'deleteImageRouteName' => 'documents.images.destroy',
            'imageOwnerId' => $ownedDocument->id,
        ]);
    }

    public function update(Request $request, int $document): RedirectResponse|JsonResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'theme_id' => [
                'nullable',
                'integer',
                Rule::exists('themes', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ]);

        $ownedDocument->update($attributes);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'document' => [
                    'id' => (int) $ownedDocument->getKey(),
                    'title' => (string) $ownedDocument->title,
                    'description' => $ownedDocument->description,
                    'theme_id' => $ownedDocument->theme_id,
                ],
            ]);
        }

        return redirect()->route('documents.edit', $ownedDocument)->with('status', 'Presentation updated successfully.');
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        $ownedDocument->delete();

        return redirect()->route('documents.index')->with('status', 'Presentation moved to trash.');
    }

    public function restore(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->onlyTrashed()->findOrFail($document);

        $ownedDocument->restore();

        return redirect()->route('documents.index')->with('status', 'Document restored.');
    }
}
