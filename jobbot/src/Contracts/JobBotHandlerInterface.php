<?php

namespace Ddt\JobBot\Contracts;

use Illuminate\Http\JsonResponse;

interface JobBotHandlerInterface
{
    /**
     * Handle the matched intent.
     *
     * @param  string      $lower     Lowercase version of user query
     * @param  string      $original  Original user query
     * @param  mixed       $user      Authenticated user (or null)
     * @return JsonResponse
     */
    public function handle(string $lower, string $original, mixed $user): JsonResponse;
}
