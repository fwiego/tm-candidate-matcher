<?php

namespace App\Services;

use App\Models\Technology;
use Illuminate\Support\Collection;

class SkillDetectionService
{
    /**
     * Detect which technologies (by id) are mentioned in the given text.
     *
     * Matches technology names and their synonyms, case-insensitively, with
     * word-boundary awareness so "Java" doesn't match inside "JavaScript".
     *
     * @return Collection<int, Technology> Matched technologies.
     */
    public function detect(string $text, ?Collection $technologies = null): Collection
    {
        $technologies ??= Technology::all();

        if (trim($text) === '') {
            return collect();
        }

        $normalizedText = $this->normalize($text);

        return $technologies->filter(function (Technology $technology) use ($normalizedText) {
            $candidates = array_merge([$technology->name], $technology->synonyms ?? []);

            foreach ($candidates as $candidate) {
                if ($this->containsTerm($normalizedText, $candidate)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * Normalize text for matching: lowercase, collapse whitespace.
     */
    protected function normalize(string $text): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Check whether a term appears in the (already normalized) text as a whole word/phrase,
     * using non-alphanumeric boundaries (so "Java" won't match inside "JavaScript",
     * but "C#" or ".NET" — which contain symbols — still match correctly).
     */
    protected function containsTerm(string $normalizedText, string $term): bool
    {
        $term = trim($term);

        if ($term === '') {
            return false;
        }

        $normalizedTerm = mb_strtolower($term);
        $quoted = preg_quote($normalizedTerm, '/');

        // If the term itself starts/ends with a "word" character, require a word boundary
        // there; if it starts/ends with a symbol (e.g. "C#", ".NET"), don't require one,
        // since \b doesn't work well around non-word characters.
        $startsWithWordChar = (bool) preg_match('/^[\p{L}\p{N}]/u', $normalizedTerm);
        $endsWithWordChar = (bool) preg_match('/[\p{L}\p{N}]$/u', $normalizedTerm);

        $prefix = $startsWithWordChar ? '(?<![\p{L}\p{N}])' : '';
        $suffix = $endsWithWordChar ? '(?![\p{L}\p{N}])' : '';

        $pattern = '/'.$prefix.$quoted.$suffix.'/u';

        return (bool) preg_match($pattern, $normalizedText);
    }
}