<?php

declare(strict_types=1);

namespace App\SlideWire;

use App\Models\Document;
use WendellAdriel\SlideWire\Contracts\DatabaseDocumentProvider as SlideWireDatabaseDocumentProvider;
use WendellAdriel\SlideWire\DTOs\DatabaseDocument;

final class DatabaseDocumentProvider implements SlideWireDatabaseDocumentProvider
{
    public function findById(int $id): ?DatabaseDocument
    {
        /** @var Document|null $document */
        $document = Document::query()->with('theme')->whereKey($id)->first();

        if ($document === null) {
            return null;
        }

        return new DatabaseDocument(
            id: (int) $document->getKey(),
            name: (string) $document->title,
            content: (string) $document->content,
            ownerId: (int) $document->user_id,
            customCss: $this->sanitizeCss($document->theme?->css),
        );
    }

    private function sanitizeCss(?string $css): ?string
    {
        if ($css === null || trim($css) === '') {
            return null;
        }

        $sanitized = preg_replace('/<\/?style\b[^>]*>/i', '', $css);

        if (! is_string($sanitized)) {
            return null;
        }

        return trim($sanitized);
    }
}
