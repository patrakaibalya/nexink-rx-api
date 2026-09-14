<?php

namespace App\Console\Commands;

use Database\Seeders\MasterAdminSeeder;
use Illuminate\Console\Command;

class SeedMasterAdmin extends Command
{
    protected $signature = 'master-admin:seed';

    protected $description = 'Seed the default master admin (admin@nexink.com)';

    public function handle(): int
    {
        (new MasterAdminSeeder())->run();

        $this->info('Master admin seeded successfully.');

        return self::SUCCESS;
    }
}
