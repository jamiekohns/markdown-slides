<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentSlideController extends Controller
{
    public function index(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        return response()->json([
            'slides' => $ownedDocument->slides
                ->sortBy('sort_order')
                ->values()
                ->map(fn (Slide $slide) => $this->formatSlide($slide)),
        ]);
    }

    public function store(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $attributes = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $nextOrder = (int) $ownedDocument->slides()->max('sort_order') + 1;

        $slide = $ownedDocument->slides()->create([
            'sort_order' => $nextOrder,
            'content' => $attributes['content'] ?? '',
        ]);

        return response()->json([
            'slide' => $this->formatSlide($slide),
        ], 201);
    }

    public function update(Request $request, int $document, int $slide): JsonResponse
    {
        $this->ownedDocument($request, $document);

        $ownedSlide = Slide::query()
            ->where('document_id', $document)
            ->whereKey($slide)
            ->firstOrFail();

        $attributes = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $ownedSlide->update(['content' => $attributes['content']]);

        return response()->json([
            'slide' => $this->formatSlide($ownedSlide->fresh()),
        ]);
    }

    public function destroy(Request $request, int $document, int $slide): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        if ($ownedDocument->slides()->count() <= 1) {
            return response()->json([
                'message' => 'A presentation must have at least one slide.',
            ], 422);
        }

        $ownedSlide = Slide::query()
            ->where('document_id', $document)
            ->whereKey($slide)
            ->firstOrFail();

        $ownedSlide->delete();
        $this->normalizeOrder($ownedDocument);

        return response()->json(['status' => 'ok']);
    }

    public function reorder(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $attributes = $request->validate([
            'slide_ids' => ['required', 'array', 'min:1'],
            'slide_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $existingIds = $ownedDocument->slides()->pluck('id')->sort()->values()->all();
        $incomingIds = collect($attributes['slide_ids'])->map(fn (mixed $id) => (int) $id)->sort()->values()->all();

        if ($existingIds !== $incomingIds) {
            return response()->json([
                'message' => 'Reorder payload must include all slides exactly once.',
            ], 422);
        }

        DB::transaction(function () use ($document, $attributes): void {
            $temporaryOffset = 100000;

            foreach ($attributes['slide_ids'] as $index => $slideId) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $slideId)
                    ->update(['sort_order' => $temporaryOffset + $index + 1]);
            }

            foreach ($attributes['slide_ids'] as $index => $slideId) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $slideId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function saveAll(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $attributes = $request->validate([
            'slides' => ['required', 'array', 'min:1'],
            'slides.*.id' => ['required', 'integer'],
            'slides.*.content' => ['required', 'string'],
        ]);

        $existingIds = $ownedDocument->slides()->pluck('id')->sort()->values()->all();
        $incomingIds = collect($attributes['slides'])
            ->pluck('id')
            ->map(fn (mixed $id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($existingIds !== $incomingIds) {
            return response()->json([
                'message' => 'Save-all payload must include all slides exactly once.',
            ], 422);
        }

        DB::transaction(function () use ($document, $attributes): void {
            $temporaryOffset = 100000;

            foreach ($attributes['slides'] as $index => $incomingSlide) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $incomingSlide['id'])
                    ->update([
                        'content' => $incomingSlide['content'],
                        'sort_order' => $temporaryOffset + $index + 1,
                    ]);
            }

            foreach ($attributes['slides'] as $index => $incomingSlide) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $incomingSlide['id'])
                    ->update([
                        'content' => $incomingSlide['content'],
                        'sort_order' => $index + 1,
                    ]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function ownedDocument(Request $request, int $document): Document
    {
        return $request->user()->documents()->with('slides')->findOrFail($document);
    }

    private function normalizeOrder(Document $document): void
    {
        $orderedIds = $document->slides()->orderBy('sort_order')->pluck('id');

        foreach ($orderedIds as $index => $slideId) {
            Slide::query()->whereKey((int) $slideId)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function formatSlide(Slide $slide): array
    {
        return [
            'id' => (int) $slide->getKey(),
            'sort_order' => (int) $slide->sort_order,
            'content' => (string) $slide->content,
            'updated_at' => $slide->updated_at?->toIso8601String(),
        ];
    }
}
