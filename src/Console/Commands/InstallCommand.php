<?php

namespace Afterburner\Communications\Console\Commands;

use Afterburner\Communications\Database\Seeders\CommunicationsPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'afterburner:communications:install';

    protected $description = 'Install the Afterburner Communications package';

    public function handle(): int
    {
        $this->info('Installing Afterburner Communications package...');

        $this->call('vendor:publish', [
            '--tag' => 'afterburner-communications-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'afterburner-communications-assets',
            '--force' => true,
        ]);

        $this->addEnvironmentVariables();

        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        if ($this->confirm('Seed communications permissions?', true)) {
            $seeder = new CommunicationsPermissionsSeeder;
            $seeder->setCommand($this);
            $seeder->run();
        }

        $this->info('Installation complete!');

        return Command::SUCCESS;
    }

    protected function addEnvironmentVariables(): void
    {
        $envVars = [
            '',
            '# Afterburner Communications',
            'AFTERBURNER_COMMUNICATIONS_ENABLED=true',
            'AFTERBURNER_COMMUNICATIONS_DISCUSSIONS_ENABLED=true',
        ];

        foreach (['.env', '.env.example'] as $file) {
            $path = base_path($file);
            if (! File::exists($path)) {
                continue;
            }

            $content = File::get($path);
            foreach ($envVars as $var) {
                if ($var && ! str_contains($content, explode('=', $var)[0])) {
                    File::append($path, "\n".$var);
                }
            }
        }
    }
}
