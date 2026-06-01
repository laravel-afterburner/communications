<?php

namespace Afterburner\Communications\Console\Commands;

use Afterburner\Communications\Database\Seeders\CommunicationsPermissionsSeeder;
use Illuminate\Console\Command;

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
}
