<?php

namespace Database\Seeders;

use App\Models\MasterAdmin;
use Illuminate\Database\Seeder;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        MasterAdmin::updateOrCreate(
            ['email' => 'admin@nexink.com'],
            [
                'name' => 'Admin',
                'password' => 'password123',
                'is_active' => true,
            ]
        );
    }
}
