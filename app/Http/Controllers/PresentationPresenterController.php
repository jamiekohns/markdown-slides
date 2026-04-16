<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PresentationPresenterController extends Controller
{
    public function __invoke(Request $request, int $document): View
    {
        $ownedDocument = $request->user()
            ->documents()
            ->with(['script'])
            ->findOrFail($document);

        $scriptMarkdown = (string) ($ownedDocument->script?->content ?? '');
        $sections = $this->scriptSections($scriptMarkdown);

        return view('presentations.presenter', [
            'document' => $ownedDocument,
            'presentationUrl' => $ownedDocument->presentationUrl(),
            'sections' => $sections,
        ]);
    }

    /**
     * @return array<int, array{index: int, html: string}>
     */
    private function scriptSections(string $scriptMarkdown): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $scriptMarkdown);
        $withoutClosingTags = preg_replace('/<\/x-slidewire::slide\s*>/i', '', $normalized);
        $content = is_string($withoutClosingTags) ? $withoutClosingTags : $normalized;

        $chunks = preg_split('/<x-slidewire::slide(?:\s[^>]*)?\s*\/?\s*>/i', $content) ?: [$content];
        $sections = [];

        foreach ($chunks as $index => $chunk) {
            $trimmed = trim((string) $chunk);
            if ($trimmed === '') {
                continue;
            }

            $sections[] = [
                'index' => $index,
                'html' => Str::markdown($trimmed),
            ];
        }

        if ($sections === []) {
            $sections[] = [
                'index' => 0,
                'html' => Str::markdown(''),
            ];
        }

        return array_values($sections);
    }
}
