<?php

declare(strict_types=1);

namespace App\Support;

final class DocumentSlideContent
{
    /**
     * @return array<int, string>
     */
    public static function extractSlideBodies(string $documentContent): array
    {
        $matches = [];
        $count = preg_match_all('/<x-slidewire::slide\b[^>]*>(.*?)<\/x-slidewire::slide>/is', $documentContent, $matches);

        if (! is_int($count) || $count < 1) {
            return [trim($documentContent)];
        }

        $bodies = array_map(
            static fn (mixed $body): string => is_string($body) ? trim($body) : '',
            $matches[1] ?? [],
        );

        return array_values($bodies);
    }

    /**
     * @param  iterable<string>  $slideBodies
     */
    public static function buildDeckMarkup(iterable $slideBodies): string
    {
        $slides = [];

        foreach ($slideBodies as $body) {
            $normalizedBody = trim((string) $body);

            $slides[] = "<x-slidewire::slide>\n<x-slidewire::markdown>\n{$normalizedBody}\n</x-slidewire::markdown>\n</x-slidewire::slide>";
        }

        $joinedSlides = implode("\n\n", $slides);

        return "<x-slidewire::deck>\n{$joinedSlides}\n</x-slidewire::deck>";
    }
}
