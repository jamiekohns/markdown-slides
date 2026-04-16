<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentScriptController extends Controller
{
    public function show(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);
        $script = $ownedDocument->script;

        return response()->json([
            'script' => [
                'id' => $script?->id,
                'content' => (string) ($script?->content ?? ''),
                'updated_at' => $script?->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $attributes = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $script = $ownedDocument->script()->updateOrCreate(
            ['document_id' => (int) $ownedDocument->getKey()],
            ['content' => $attributes['content']]
        );

        return response()->json([
            'status' => 'ok',
            'script' => [
                'id' => (int) $script->getKey(),
                'content' => (string) $script->content,
                'updated_at' => $script->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function ownedDocument(Request $request, int $document): Document
    {
        return $request->user()->documents()->with('script')->findOrFail($document);
    }
}
