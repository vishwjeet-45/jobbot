<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Key
    |--------------------------------------------------------------------------
    | AI fallback aur resume parsing ke liye use hota hai.
    | .env me OPENROUTER_KEY set karein.
    */
    'openrouter_key' => env('OPENROUTER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OpenRouter Model
    |--------------------------------------------------------------------------
    */
    'openrouter_model' => env('JOBBOT_AI_MODEL', 'nex-agi/nex-n2-pro:free'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix & Middleware
    |--------------------------------------------------------------------------
    | Default: /jobbot/query, /jobbot/resume
    | Middleware change karna ho to yahan set karein.
    */
    'route_prefix'  => 'jobbot',
    'middleware'    => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Intents JSON Path
    |--------------------------------------------------------------------------
    | Custom intents use karne ke liye apna JSON file path dein.
    | Default: package ka default_intents.json use hoga.
    |
    | Example: base_path('jobbot/intents.json')
    */
    'intents_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Intent Handlers (Custom Override)
    |--------------------------------------------------------------------------
    | Kisi bhi built-in handler ko apni class se replace kar sakte hain.
    |
    | Key   → intent handler name (JSON ka "handler" field)
    | Value → fully qualified class implementing JobBotHandlerInterface
    |
    | Example:
    | 'handlers' => [
    |     'find_candidates' => \App\JobBot\FindCandidatesHandler::class,
    | ],
    */
    'handlers' => [],

    /*
    |--------------------------------------------------------------------------
    | Bot UI Config
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'bot_name'    => env('JOBBOT_NAME', 'Job Sphere Assistant'),
        'bot_initials'=> env('JOBBOT_INITIALS', 'JS'),
        'primary_color' => '#7c3aed',
        'greeting'    => 'Namaste! 👋 Main <strong>Job Sphere Assistant</strong> hoon.<br>Candidates dhundein, jobs search karein, resume parse karein ya candidate add karein!',
        'quick_chips' => [
            ['label' => '🔍 Laravel Candidates', 'query' => 'Laravel developer candidates dhundo'],
            ['label' => '💼 React Jobs',          'query' => 'React ke liye jobs'],
            ['label' => '📄 Parse Resume',         'query' => 'Resume upload'],
            ['label' => '➕ Add Candidate',        'query' => 'Add candidate'],
            ['label' => '❓ Help',                 'query' => 'help'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resume Parsing
    |--------------------------------------------------------------------------
    */
    'resume' => [
        'max_size_kb'  => 5120,
        'allowed_mime' => ['pdf', 'doc', 'docx'],
        'ai_enabled'   => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (minutes)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => 5,

    /*
    |--------------------------------------------------------------------------
    | Query Limit Per-Search
    |--------------------------------------------------------------------------
    */
    'search_limit' => 20,

];
