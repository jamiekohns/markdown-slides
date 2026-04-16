<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PresentationPresenterController extends Controller
{
    public function __invoke(Request $request, int $document): View
    {
        $ownedDocument = $request->user()
            ->documents()
            ->with(['slides'])
            ->findOrFail($document);

        $sections = $this->scriptSections($ownedDocument->slides->all());

        return view('presentations.presenter', [
            'document' => $ownedDocument,
            'presentationUrl' => $ownedDocument->presentationUrl(),
            'sections' => $sections,
        ]);
    }

    /**
     * @param  array<int, Slide>  $slides
     * @return array<int, array{index: int, html: string}>
     */
    private function scriptSections(array $slides): array
    {
        $sections = collect($slides)
            ->sortBy('sort_order')
            ->values()
            ->map(function (Slide $slide, int $index): array {
                return [
                    'index' => $index,
                    'html' => Str::markdown((string) ($slide->script ?? '')),
                ];
            })
            ->all();

        if ($sections === []) {
            $sections[] = [
                'index' => 0,
                'html' => Str::markdown(''),
            ];
        }

        return array_values($sections);
    }
}
