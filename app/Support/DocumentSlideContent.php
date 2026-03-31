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
        $splitMatches = [];
        $containsSlideTags = preg_match('/<x-slidewire::slide\b[^>]*>/i', $documentContent, $splitMatches);

        if (! is_int($containsSlideTags) || $containsSlideTags !== 1) {
            return [trim($documentContent)];
        }

        $segments = preg_split('/<x-slidewire::slide\b[^>]*>/i', $documentContent);

        if (! is_array($segments) || count($segments) < 2) {
            return [trim($documentContent)];
        }

        array_shift($segments);

        $bodies = array_map(static function (mixed $segment): string {
            $body = is_string($segment) ? $segment : '';
            $body = preg_replace('/<\/x-slidewire::slide>/i', '', $body);
            $body = preg_replace('/<\/?x-slidewire::markdown\b[^>]*>/i', '', $body);
            $body = preg_replace('/<\/?x-slidewire::deck\b[^>]*>/i', '', $body);

            return trim(is_string($body) ? $body : '');
        }, $segments);

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
