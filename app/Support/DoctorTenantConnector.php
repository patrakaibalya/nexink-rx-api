<?php

namespace App\Support;

use App\Models\DoctorDatabase;
use Illuminate\Support\Facades\DB;

class DoctorTenantConnector
{
    /**
     * Configure and reconnect the `doctor` connection to the given
     * doctor's tenant database. Returns the DoctorDatabase record on
     * success, or null if it does not exist or is not active.
     */
    public static function connect(int $doctorId): ?DoctorDatabase
    {
        $doctorDatabase = DoctorDatabase::query()
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$doctorDatabase || $doctorDatabase->status !== 'active') {
            return null;
        }

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

        DB::purge('doctor');
        DB::reconnect('doctor');

        return $doctorDatabase;
    }
}
