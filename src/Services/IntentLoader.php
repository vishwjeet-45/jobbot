<?php

namespace Ddt\JobBot\Services;

use Ddt\JobBot\Contracts\JobBotHandlerInterface;
use Ddt\JobBot\Handlers\{
    FindCandidatesHandler,
    FindJobsHandler,
    ParseResumeHandler,
    AddCandidateHandler,
    MyProfileHandler,
    HelpHandler,
    SaveResumeHandler
};
use Illuminate\Support\Facades\Log;

class IntentLoader
{
    /**
     * Built-in handler map: intent key → class
     */
    private array $builtinHandlers = [
        'find_candidates' => FindCandidatesHandler::class,
        'find_jobs'       => FindJobsHandler::class,
        'parse_resume'    => ParseResumeHandler::class,
        'add_candidate'   => AddCandidateHandler::class,
        'my_profile'      => MyProfileHandler::class,
        'help'            => HelpHandler::class,
        'save_resume'     => SaveResumeHandler::class,
    ];

    private array $intents = [];

    public function __construct()
    {
        $this->loadIntents();
    }

    /**
     * Load intents from JSON file.
     * Priority: config('jobbot.intents_path') → package default
     */
    private function loadIntents(): void
    {
        $customPath = config('jobbot.intents_path');

        $path = ($customPath && file_exists($customPath))
            ? $customPath
            : __DIR__ . '/../../intents/default_intents.json';

        if (!file_exists($path)) {
            Log::error("JobBot: Intents file not found at {$path}");
            $this->intents = [];
            return;
        }

        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JobBot: Invalid JSON in intents file', ['error' => json_last_error_msg()]);
            $this->intents = [];
            return;
        }

        $this->intents = $decoded;
    }

    /**
     * Detect intent from lowercase query string.
     * Returns intent array or null.
     */
    public function detect(string $lowerQuery): ?array
    {
        foreach ($this->intents as $intent) {
            foreach ($intent['patterns'] as $pattern) {
                if (str_contains($lowerQuery, $pattern)) {
                    return $intent;
                }
            }
        }
        return null;
    }

    /**
     * Resolve handler instance for a given intent handler key.
     * Config overrides take priority over built-ins.
     */
    public function resolveHandler(string $handlerKey): ?JobBotHandlerInterface
    {
        // 1. Check config override
        $customHandlers = config('jobbot.handlers', []);

        if (!empty($customHandlers[$handlerKey])) {
            $class = $customHandlers[$handlerKey];

            if (!class_exists($class)) {
                Log::error("JobBot: Custom handler class not found: {$class}");
                return null;
            }

            $instance = app($class);

            if (!$instance instanceof JobBotHandlerInterface) {
                Log::error("JobBot: {$class} must implement JobBotHandlerInterface");
                return null;
            }

            return $instance;
        }

        // 2. Built-in handler
        if (isset($this->builtinHandlers[$handlerKey])) {
            return app($this->builtinHandlers[$handlerKey]);
        }

        Log::warning("JobBot: No handler found for key: {$handlerKey}");
        return null;
    }

    /**
     * Get all loaded intents (for debugging).
     */
    public function getIntents(): array
    {
        return $this->intents;
    }
}
