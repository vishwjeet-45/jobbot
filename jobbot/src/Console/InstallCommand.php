<?php

namespace Ddt\JobBot\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature   = 'jobbot:install';
    protected $description = 'Install and configure the JobBot package';

    public function handle(): void
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════╗');
        $this->info('  ║      JobBot Installer v1.0       ║');
        $this->info('  ╚══════════════════════════════════╝');
        $this->info('');

        $this->publishConfig();
        $this->publishViews();
        $this->publishIntents();
        $this->checkEnvKeys();
        $this->printNextSteps();
    }

    // ─────────────────────────────────────────────
    private function publishConfig(): void
    {
        $this->info('📦 Publishing config...');

        $this->callSilent('vendor:publish', [
            '--tag'   => 'jobbot-config',
            '--force' => false,
        ]);

        if (file_exists(config_path('jobbot.php'))) {
            $this->line('   <fg=green>✔</> config/jobbot.php published');
        } else {
            $this->line('   <fg=yellow>⚠</> config/jobbot.php already exists — skipped');
        }
    }

    private function publishViews(): void
    {
        $this->info('🎨 Publishing views...');

        $this->callSilent('vendor:publish', [
            '--tag'   => 'jobbot-views',
            '--force' => false,
        ]);

        $viewPath = resource_path('views/vendor/jobbot/components/job-bot.blade.php');

        if (file_exists($viewPath)) {
            $this->line('   <fg=green>✔</> resources/views/vendor/jobbot/ published');
        } else {
            $this->line('   <fg=yellow>⚠</> Views already exist — skipped');
        }
    }

    private function publishIntents(): void
    {
        $this->info('🧠 Publishing intents JSON...');

        $dest = base_path('jobbot/intents.json');

        if (!file_exists($dest)) {
            File::ensureDirectoryExists(dirname($dest));
            File::copy(
                __DIR__ . '/../../intents/default_intents.json',
                $dest
            );
            $this->line('   <fg=green>✔</> jobbot/intents.json created — customize your intents here!');
        } else {
            $this->line('   <fg=yellow>⚠</> jobbot/intents.json already exists — skipped');
        }
    }

    private function checkEnvKeys(): void
    {
        $this->info('🔑 Checking .env keys...');

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->line('   <fg=yellow>⚠</> .env file not found — skipping');
            return;
        }

        $envContent = file_get_contents($envPath);
        $added      = [];

        $keysToCheck = [
            'OPENROUTER_KEY'   => '',
            'JOBBOT_AI_MODEL'  => 'nex-agi/nex-n2-pro:free',
            'JOBBOT_NAME'      => 'Job Sphere Assistant',
            'JOBBOT_INITIALS'  => 'JS',
        ];

        foreach ($keysToCheck as $key => $default) {
            if (!str_contains($envContent, $key)) {
                $line = $default ? "{$key}={$default}" : "{$key}=";
                file_put_contents($envPath, "\n{$line}", FILE_APPEND);
                $added[] = $key;
            }
        }

        if (!empty($added)) {
            $this->line('   <fg=green>✔</> Added to .env: ' . implode(', ', $added));
        } else {
            $this->line('   <fg=green>✔</> All .env keys already present');
        }
    }

    private function printNextSteps(): void
    {
        $this->info('');
        $this->info('  ✅ <fg=green>JobBot installed successfully!</>');
        $this->info('');
        $this->line('  <fg=cyan>Next Steps:</>');
        $this->line('');
        $this->line('  1. Set your OpenRouter API key in <fg=yellow>.env</>:');
        $this->line('     <fg=gray>OPENROUTER_KEY=your_key_here</>');
        $this->line('');
        $this->line('  2. Add component to your Blade layout:');
        $this->line('     <fg=gray>@include(\'jobbot::components.job-bot\')</>');
        $this->line('');
        $this->line('  3. (Optional) Customize intents:');
        $this->line('     <fg=gray>Edit jobbot/intents.json</>');
        $this->line('     Then set in config/jobbot.php:');
        $this->line('     <fg=gray>\'intents_path\' => base_path(\'jobbot/intents.json\')</>');
        $this->line('');
        $this->line('  4. (Optional) Override a handler in config/jobbot.php:');
        $this->line("     <fg=gray>'handlers' => [");
        $this->line("         'find_candidates' => \\App\\JobBot\\MyCustomHandler::class,");
        $this->line("     ]</>");
        $this->line('');
        $this->line('  📘 Docs: https://github.com/ddt/jobbot');
        $this->info('');
    }
}
