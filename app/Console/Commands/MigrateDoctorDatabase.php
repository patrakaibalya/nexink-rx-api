<?php

namespace App\Console\Commands;

use App\Models\DoctorDatabase;
use App\Services\Database\DoctorDatabaseMigrationService;
use Illuminate\Console\Command;

class MigrateDoctorDatabase extends Command
{
    protected $signature = 'doctor:migrate {doctor_id}';

    protected $description = 'Run pending migrations for a doctor database';

    public function handle(
        DoctorDatabaseMigrationService $migrationService
    ): int {
        $doctorId = (int) $this->argument('doctor_id');

        $doctorDatabase = DoctorDatabase::where(
            'doctor_id',
            $doctorId
        )->first();

        if (!$doctorDatabase) {
            $this->error(
                "Doctor database not found for doctor ID {$doctorId}."
            );

            return self::FAILURE;
        }

        if ($doctorDatabase->status !== 'active') {
            $this->error(
                "Doctor database is not active."
            );

            return self::FAILURE;
        }

        $migrationService->migrate($doctorDatabase);

        $this->info(
            "Doctor database migrated successfully: "
                . $doctorDatabase->database_name
        );

        return self::SUCCESS;
    }
}
