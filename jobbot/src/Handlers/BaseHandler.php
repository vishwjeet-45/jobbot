<?php

namespace Ddt\JobBot\Handlers;

use Ddt\JobBot\Contracts\JobBotHandlerInterface;
use Illuminate\Http\JsonResponse;

abstract class BaseHandler implements JobBotHandlerInterface
{
    /**
     * Build standard JSON response.
     */
    protected function respond(string $message, ?array $data): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Extract meaningful skill/keyword from a query string,
     * stripping common stop-words.
     */
    protected function extractSkillName(string $query): ?string
    {
        $stopWords = [
            'find', 'search', 'dhundo', 'chahiye', 'ke liye', 'for', 'with',
            'candidate', 'candidates', 'job', 'jobs', 'skill', 'wale', 'wala',
            'available', 'list', 'by', 'using', 'in', 'the', 'a', 'an', 'position',
        ];

        $words    = explode(' ', $query);
        $filtered = array_filter($words, fn($w) => !in_array($w, $stopWords) && strlen($w) > 1);

        return count($filtered) ? implode(' ', $filtered) : null;
    }
}
