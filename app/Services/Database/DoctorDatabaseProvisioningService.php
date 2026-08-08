<?php

namespace App\Services\Database;

use App\Models\DoctorAccount;
use App\Models\DoctorDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DoctorDatabaseProvisioningService
{
    public function provision(DoctorAccount $doctor): DoctorDatabase
    {
        $databaseName = $this->getDatabaseName($doctor->id);

        $doctorDatabase = DoctorDatabase::create([
            'doctor_id' => $doctor->id,
            'database_name' => $databaseName,
            'database_host' => config('database.connections.mysql.host'),
            'database_port' => config('database.connections.mysql.port'),
            'database_username' => config('database.connections.mysql.username'),
            'database_password' => config('database.connections.mysql.password'),
            'status' => 'provisioning',
        ]);

        try {
            $this->createDatabase($databaseName);

            $this->runMigrations($databaseName);

            $doctorDatabase->update([
                'status' => 'active',
                'provisioned_at' => now(),
            ]);

            return $doctorDatabase->fresh();
        } catch (Throwable $exception) {
            $doctorDatabase->update([
                'status' => 'failed',
            ]);

            Log::error('Doctor database provisioning failed.', [
                'doctor_id' => $doctor->id,
                'database_name' => $databaseName,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function getDatabaseName(int $doctorId): string
    {
        return 'doctor_db_' . $doctorId;
    }

    private function createDatabase(string $databaseName): void
    {
        $databaseName = str_replace('`', '``', $databaseName);

        DB::connection('mysql')->statement(
            "CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private function runMigrations(string $databaseName): void
    {
        config([
            'database.connections.doctor' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $databaseName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'unix_socket' => config('database.connections.mysql.unix_socket'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);

        DB::purge('doctor');

        DB::reconnect('doctor');

        Artisan::call('migrate', [
            '--database' => 'doctor',
            '--path' => 'database/doctor-migrations',
            '--force' => true,
        ]);
    }
}
