<?php

namespace App\Services\Database;

use App\Models\DoctorDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DoctorDatabaseMigrationService
{
    public function migrate(DoctorDatabase $doctorDatabase): void
    {
        $this->configureConnection($doctorDatabase);

        DB::purge('doctor');

        DB::reconnect('doctor');

        try {
            Artisan::call('migrate', [
                '--database' => 'doctor',
                '--path' => 'database/doctor-migrations',
                '--force' => true,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Doctor database migration failed: '
                . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    private function configureConnection(
        DoctorDatabase $doctorDatabase
    ): void {
        config([
            'database.connections.doctor' => [
                'driver' => 'mysql',
                'host' => $doctorDatabase->database_host,
                'port' => $doctorDatabase->database_port,
                'database' => $doctorDatabase->database_name,
                'username' => $doctorDatabase->database_username,
                'password' => $doctorDatabase->database_password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);
    }
}
