<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Slide;
use App\Support\DocumentSlideContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $nextOrder = (int) $ownedDocument->slides()->max('sort_order') + 1;
        $title = $this->normalizeTitle($attributes['title'] ?? null, $nextOrder);

        if ($this->documentHasDuplicateTitle($ownedDocument, $title)) {
            return response()->json([
                'message' => 'Slide titles must be unique within a presentation.',
            ], 422);
        }

        $slide = $ownedDocument->slides()->create([
            'sort_order' => $nextOrder,
            'title' => $title,
            'content' => $attributes['content'] ?? '',
        ]);

        return response()->json([
            'slide' => $this->formatSlide($slide),
        ], 201);
    }

    public function update(Request $request, int $document, int $slide): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $ownedSlide = Slide::query()
            ->where('document_id', $document)
            ->whereKey($slide)
            ->firstOrFail();

        $attributes = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $title = $this->normalizeTitle($attributes['title'] ?? null, (int) $ownedSlide->sort_order);

        if ($this->documentHasDuplicateTitle($ownedDocument, $title, (int) $ownedSlide->getKey())) {
            return response()->json([
                'message' => 'Slide titles must be unique within a presentation.',
            ], 422);
        }

        $ownedSlide->update([
            'title' => $title,
            'content' => $attributes['content'],
        ]);

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
            'slides.*.title' => ['nullable', 'string', 'max:255'],
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

        $normalizedTitles = collect($attributes['slides'])
            ->values()
            ->map(fn (array $incomingSlide, int $index) => $this->normalizedTitleKey(
                $this->normalizeTitle($incomingSlide['title'] ?? null, $index + 1)
            ));

        if ($normalizedTitles->duplicates()->isNotEmpty()) {
            return response()->json([
                'message' => 'Slide titles must be unique within a presentation.',
            ], 422);
        }

        DB::transaction(function () use ($document, $attributes): void {
            $temporaryOffset = 100000;

            foreach ($attributes['slides'] as $index => $incomingSlide) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $incomingSlide['id'])
                    ->update([
                        'title' => $this->normalizeTitle($incomingSlide['title'] ?? null, $index + 1),
                        'content' => $incomingSlide['content'],
                        'sort_order' => $temporaryOffset + $index + 1,
                    ]);
            }

            foreach ($attributes['slides'] as $index => $incomingSlide) {
                Slide::query()
                    ->where('document_id', $document)
                    ->whereKey((int) $incomingSlide['id'])
                    ->update([
                        'title' => $this->normalizeTitle($incomingSlide['title'] ?? null, $index + 1),
                        'content' => $incomingSlide['content'],
                        'sort_order' => $index + 1,
                    ]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function export(Request $request, int $document): Response
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $content = DocumentSlideContent::buildDeckMarkup(
            $ownedDocument->slides()->orderBy('sort_order')->pluck('content')
        );

        $slug = Str::slug((string) $ownedDocument->title);
        $filename = ($slug !== '' ? $slug : 'presentation') . '.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request, int $document): JsonResponse
    {
        $ownedDocument = $this->ownedDocument($request, $document);

        $attributes = $request->validate([
            'markdown_file' => ['required_without:content', 'file', 'max:2048'],
            'content' => ['required_without:markdown_file', 'string'],
        ]);

        $rawContent = $attributes['content'] ?? null;

        if ($request->hasFile('markdown_file')) {
            $fileContent = $request->file('markdown_file')?->get();

            if (! is_string($fileContent)) {
                return response()->json([
                    'message' => 'Uploaded markdown file could not be read.',
                ], 422);
            }

            $rawContent = $fileContent;
        }

        $slides = DocumentSlideContent::extractSlideBodies((string) $rawContent);

        DB::transaction(function () use ($ownedDocument, $slides): void {
            $ownedDocument->slides()->delete();

            foreach ($slides as $index => $content) {
                $ownedDocument->slides()->create([
                    'sort_order' => $index + 1,
                    'title' => 'Slide ' . ($index + 1),
                    'content' => $content,
                ]);
            }
        });

        $freshDocument = $ownedDocument->fresh(['slides']);

        return response()->json([
            'status' => 'ok',
            'imported_count' => count($slides),
            'slides' => $freshDocument?->slides
                ->sortBy('sort_order')
                ->values()
                ->map(fn (Slide $slide) => $this->formatSlide($slide)),
        ]);
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
            'title' => $slide->title,
            'content' => (string) $slide->content,
            'updated_at' => $slide->updated_at?->toIso8601String(),
        ];
    }

    private function normalizeTitle(mixed $title, int $sortOrder): string
    {
        $normalized = is_string($title) ? trim($title) : '';

        return $normalized !== '' ? $normalized : 'Slide ' . $sortOrder;
    }

    private function documentHasDuplicateTitle(Document $document, string $title, ?int $ignoreSlideId = null): bool
    {
        $normalizedTitleKey = $this->normalizedTitleKey($title);

        return $document->slides->contains(function (Slide $slide) use ($normalizedTitleKey, $ignoreSlideId): bool {
            if ($ignoreSlideId !== null && (int) $slide->getKey() === $ignoreSlideId) {
                return false;
            }

            return $this->normalizedTitleKey((string) ($slide->title ?? '')) === $normalizedTitleKey;
        });
    }

    private function normalizedTitleKey(string $title): string
    {
        return mb_strtolower(trim($title));
    }
}
