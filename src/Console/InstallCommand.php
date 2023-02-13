<?php

namespace Evandersonluks\Carpenter\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carpenter:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Config file publish command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('vendor:publish', ['--tag' => 'carpenter']);
        shell_exec('chown -R sail config/carpenter.php');

        return $this->info('carpenter config file create successfully!');
    }
}