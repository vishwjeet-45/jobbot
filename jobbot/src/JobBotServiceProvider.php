<?php

namespace Ddt\JobBot;

use Ddt\JobBot\Console\InstallCommand;
use Ddt\JobBot\Services\IntentLoader;
use Ddt\JobBot\Services\ResumeParserService;
use Illuminate\Support\ServiceProvider;

class JobBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/Config/jobbot.php', 'jobbot');

        // Bind services as singletons
        $this->app->singleton(IntentLoader::class);
        $this->app->singleton(ResumeParserService::class);
    }

    public function boot(): void
    {
        // ── Routes ──
        $this->loadRoutesFrom(__DIR__ . '/../routes/jobbot.php');

        // ── Views ──
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jobbot');

        // ── Publishable assets ──
        if ($this->app->runningInConsole()) {

            // Config
            $this->publishes([
                __DIR__ . '/Config/jobbot.php' => config_path('jobbot.php'),
            ], 'jobbot-config');

            // Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/jobbot'),
            ], 'jobbot-views');

            // Intents JSON (to project root /jobbot/)
            $this->publishes([
                __DIR__ . '/../intents/default_intents.json' => base_path('jobbot/intents.json'),
            ], 'jobbot-intents');

            // All at once
            $this->publishes([
                __DIR__ . '/Config/jobbot.php'               => config_path('jobbot.php'),
                __DIR__ . '/../resources/views'              => resource_path('views/vendor/jobbot'),
                __DIR__ . '/../intents/default_intents.json' => base_path('jobbot/intents.json'),
            ], 'jobbot');

            // Commands
            $this->commands([InstallCommand::class]);
        }
    }
}
