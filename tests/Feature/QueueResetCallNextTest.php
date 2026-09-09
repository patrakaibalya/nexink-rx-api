<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\Visit;
use App\Services\Queue\QueueService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueResetCallNextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.doctor' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
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

    protected function makeClinic(): Clinic
    {
        return Clinic::create([
            'name' => 'Test Clinic',
            'address' => 'Somewhere',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'mobile' => '9876543210',
            'email' => 'clinic@example.com',
            'timezone' => 'Asia/Kolkata',
            'appointment_duration_minutes' => 30,
            'is_active' => true,
        ]);
    }

    protected function makePatient(): Patient
    {
        return Patient::create([
            'name' => 'Test Patient',
            'mobile' => '9876543212',
            'email' => 'patient@example.com',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
        ]);
    }

    public function test_reset_reverts_a_called_queue_to_waiting(): void
    {
        $clinic = $this->makeClinic();
        $patient = $this->makePatient();

        $queue = Queue::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_number' => 1,
            'queue_date' => now()->toDateString(),
            'source' => 'web',
            'status' => 'called',
            'arrived_at' => now(),
            'called_at' => now(),
        ]);

        $result = (new QueueService())->resetCallNext($clinic->id);

        $this->assertSame($queue->id, $result->id);
        $this->assertSame('waiting', $result->status);
        $this->assertNull($result->called_at);
    }

    public function test_reset_deletes_visit_and_draft_prescription_for_consulting_queue(): void
    {
        $clinic = $this->makeClinic();
        $patient = $this->makePatient();

        $queue = Queue::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_number' => 1,
            'queue_date' => now()->toDateString(),
            'source' => 'web',
            'status' => 'consulting',
            'arrived_at' => now(),
            'called_at' => now(),
            'consultation_started_at' => now(),
        ]);

        $visit = Visit::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_id' => $queue->id,
            'visit_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $prescription = Prescription::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'prescription_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $result = (new QueueService())->resetCallNext($clinic->id);

        $this->assertSame('waiting', $result->status);
        $this->assertNull($result->called_at);
        $this->assertNull($result->consultation_started_at);

        $this->assertDatabaseMissing(
            (new Visit())->getTable(),
            ['id' => $visit->id],
            'doctor'
        );

        $this->assertDatabaseMissing(
            (new Prescription())->getTable(),
            ['id' => $prescription->id],
            'doctor'
        );
    }

    public function test_reset_refuses_when_prescription_already_finalized(): void
    {
        $clinic = $this->makeClinic();
        $patient = $this->makePatient();

        $queue = Queue::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_number' => 1,
            'queue_date' => now()->toDateString(),
            'source' => 'web',
            'status' => 'consulting',
            'arrived_at' => now(),
            'called_at' => now(),
            'consultation_started_at' => now(),
        ]);

        $visit = Visit::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_id' => $queue->id,
            'visit_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $prescription = Prescription::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'prescription_date' => now()->toDateString(),
            'status' => 'finalized',
        ]);

        $this->expectException(ValidationException::class);

        try {
            (new QueueService())->resetCallNext($clinic->id);
        } finally {
            $this->assertDatabaseHas(
                (new Visit())->getTable(),
                ['id' => $visit->id],
                'doctor'
            );

            $this->assertDatabaseHas(
                (new Prescription())->getTable(),
                ['id' => $prescription->id],
                'doctor'
            );

            $this->assertSame(
                'consulting',
                $queue->fresh()->status
            );
        }
    }

    public function test_reset_refuses_when_visit_already_completed(): void
    {
        $clinic = $this->makeClinic();
        $patient = $this->makePatient();

        $queue = Queue::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_number' => 1,
            'queue_date' => now()->toDateString(),
            'source' => 'web',
            'status' => 'consulting',
            'arrived_at' => now(),
            'called_at' => now(),
            'consultation_started_at' => now(),
        ]);

        Visit::create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'queue_id' => $queue->id,
            'visit_date' => now()->toDateString(),
            'started_at' => now(),
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        $this->expectException(ValidationException::class);

        (new QueueService())->resetCallNext($clinic->id);
    }

    public function test_reset_throws_when_nothing_to_reset(): void
    {
        $clinic = $this->makeClinic();

        $this->expectException(ValidationException::class);

        (new QueueService())->resetCallNext($clinic->id);
    }
}
