<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\SlideWire\DatabaseDocumentProvider;
use Illuminate\View\View;

class PresentationController extends Controller
{
    public function __invoke(string $slug): View
    {
        $document = Document::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('presentations.show', [
            'documentKey' => (string) $document->getKey(),
            'documentProvider' => DatabaseDocumentProvider::class,
        ]);
    }
}
