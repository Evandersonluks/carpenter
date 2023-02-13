<?php

namespace Evandersonluks\Carpenter\Console;

use Evandersonluks\Carpenter\EntityBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carpenter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Entity builder based in "builder" config file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $builder = (new EntityBuilder(config('carpenter')))->init();

        $var = explode(' ', implode(' ', array_keys($builder)));
        foreach ($var as $value) {
            shell_exec('chmod 777 -R "resources/views/' . Str::lower(Str::plural(explode('|', $value)[0]) . '"'));
        }
        shell_exec('chown -R sail ' . base_path());

        Artisan::call('config:clear');
        Artisan::call('migrate');
        $this->info('Running migrations.');
        Artisan::call('db:seed --class=PermissionSeeder');
        Artisan::call('db:seed --class=RoleSeeder');
        $this->info('Creating permissions.');
        Artisan::call('config:cache');

        return $this->info('Entities created successfully!');
    }
}