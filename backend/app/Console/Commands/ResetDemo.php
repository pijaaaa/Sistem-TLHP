<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset database development (migrate:fresh --seed) lalu buat data demo lintas status.';

    public function handle(): int
    {
        $this->call('migrate:fresh', ['--seed' => true]);
        $this->callSilently(DemoSeeder::class);

        $this->info('Demo siap. Login contoh: admin_spi@example.com / password (DEV_USER_PASSWORD).');

        return self::SUCCESS;
    }
}