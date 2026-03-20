<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $documents = $request->user()->documents()->latest()->get();
        $deletedDocuments = $request->user()->documents()->onlyTrashed()->latest('deleted_at')->get();

        return view('documents.index', [
            'documents' => $documents,
            'deletedDocuments' => $deletedDocuments,
        ]);
    }

    public function create(): View
    {
        return view('documents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
        ]);

        $document = $request->user()->documents()->create($attributes);

        return redirect()->route('documents.show', $document)->with('status', 'Document created successfully.');
    }

    public function show(Request $request, int $document): View
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        return view('documents.show', ['document' => $ownedDocument]);
    }

    public function edit(Request $request, int $document): View
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        return view('documents.edit', ['document' => $ownedDocument]);
    }

    public function update(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
        ]);

        $ownedDocument->update($attributes);

        return redirect()->route('documents.show', $ownedDocument)->with('status', 'Document updated successfully.');
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->findOrFail($document);

        $ownedDocument->delete();

        return redirect()->route('documents.index')->with('status', 'Document moved to trash.');
    }

    public function restore(Request $request, int $document): RedirectResponse
    {
        $ownedDocument = $request->user()->documents()->onlyTrashed()->findOrFail($document);

        $ownedDocument->restore();

        return redirect()->route('documents.index')->with('status', 'Document restored.');
    }
}
