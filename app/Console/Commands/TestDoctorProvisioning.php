<?php

namespace App\Console\Commands;

use App\Models\DoctorAccount;
use App\Services\Database\DoctorDatabaseProvisioningService;
use Illuminate\Console\Command;

class TestDoctorProvisioning extends Command
{
    protected $signature = 'test:doctor-provisioning';

    protected $description = 'Test doctor database provisioning';

    public function handle(
        DoctorDatabaseProvisioningService $provisioningService
    ): int {
        $doctor = DoctorAccount::create([
            'name' => 'Dr. Test',
            'email' => 'doctor.test@example.com',
            'mobile' => '9999999999',
            'specialization' => 'General Medicine',
            'medical_license_number' => 'TEST-001',
            'password' => 'password',
        ]);

        $doctorDatabase = $provisioningService->provision($doctor);

        $this->info('Doctor created successfully.');
        $this->info("Doctor ID: {$doctor->id}");
        $this->info("Database: {$doctorDatabase->database_name}");
        $this->info("Status: {$doctorDatabase->status}");

        return self::SUCCESS;
    }
}
