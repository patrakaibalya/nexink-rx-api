<?php

namespace App\Services\Patient;

use App\Models\Patient;
use App\Models\PatientVital;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Per-visit vitals history. The patient record's own weight / height / BP /
 * diabetic / thyroid columns are kept equal to the latest known reading of
 * each field, so older clients that read the patient record keep working.
 */
class PatientVitalService
{
    /**
     * Patient columns mirrored from the latest vitals.
     */
    private const PATIENT_COLUMNS = [
        'weight',
        'height',
        'blood_pressure_result',
        'diabetic_result',
        'thyroid_result',
    ];

    /**
     * Visit statuses meaning the patient is with the doctor right now.
     */
    private const OPEN_VISIT_STATUSES = [
        'in_progress',
        'consulting',
    ];

    public function list(int $patientId, int $perPage = 20): LengthAwarePaginator
    {
        $this->findPatient($patientId);

        return PatientVital::query()
            ->with('visit:id,visit_date,status')
            ->where('patient_id', $patientId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * The most recent non-empty value of every reading field, for pre-filling
     * the next recording. Fields never recorded are null.
     */
    public function latestValues(int $patientId): array
    {
        $vitals = PatientVital::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        $latest = [];

        foreach (PatientVital::READING_FIELDS as $field) {
            $latest[$field] = $vitals->first(fn ($vital) => filled($vital->{$field}))?->{$field};
        }

        $latest['recorded_at'] = $vitals->first()?->recorded_at;

        return $latest;
    }

    public function store(int $patientId, array $data): PatientVital
    {
        return DB::connection('doctor')->transaction(function () use ($patientId, $data) {
            $patient = $this->findPatient($patientId);

            $visit = $this->resolveVisit($patient, $data['visit_id'] ?? null);

            $vital = PatientVital::create([
                ...$data,
                'patient_id' => $patient->id,
                'visit_id' => $visit?->id,
                'clinic_id' => $data['clinic_id'] ?? $visit?->clinic_id,
                'recorded_at' => now(),
            ]);

            $this->syncPatient($patient);

            return $vital->fresh('visit:id,visit_date,status');
        });
    }

    public function update(int $patientId, int $vitalId, array $data): PatientVital
    {
        return DB::connection('doctor')->transaction(function () use ($patientId, $vitalId, $data) {
            $patient = $this->findPatient($patientId);
            $vital = $this->findVital($patientId, $vitalId);

            $vital->update(collect($data)->except(['visit_id', 'clinic_id'])->all());

            $this->syncPatient($patient);

            return $vital->fresh('visit:id,visit_date,status');
        });
    }

    public function delete(int $patientId, int $vitalId): void
    {
        DB::connection('doctor')->transaction(function () use ($patientId, $vitalId) {
            $patient = $this->findPatient($patientId);

            $this->findVital($patientId, $vitalId)->delete();

            $this->syncPatient($patient);
        });
    }

    /**
     * Records a history entry when readings arrive through the patient
     * registration / profile endpoints, so no change bypasses the history.
     */
    public function recordFromProfile(Patient $patient, array $data, array $previous = []): void
    {
        $readings = collect($data)
            ->only(self::PATIENT_COLUMNS)
            ->filter(fn ($value) => filled($value));

        $changed = $readings->contains(
            fn ($value, $field) => !$this->sameValue($value, $previous[$field] ?? null)
        );

        if ($readings->isEmpty() || !$changed) {
            return;
        }

        PatientVital::create([
            ...$readings->all(),
            'patient_id' => $patient->id,
            'visit_id' => $this->openVisit($patient)?->id,
            'recorded_by' => 'Patient profile',
            'recorded_at' => now(),
        ]);
    }

    /**
     * "55.7" and "55.70" are the same weight; text readings compare exactly.
     */
    private function sameValue(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.001;
        }

        return trim((string) $a) === trim((string) $b);
    }

    /**
     * Vitals as they were for a visit: the reading linked to the visit, or
     * else the latest reading taken on or before the visit day (in the
     * clinic's timezone). Null when nothing had been recorded yet.
     */
    public function forVisit(?Visit $visit): ?PatientVital
    {
        if (!$visit) {
            return null;
        }

        $linked = PatientVital::query()
            ->where('visit_id', $visit->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if ($linked || !$visit->visit_date) {
            return $linked;
        }

        $timezone = $visit->clinic?->timezone ?: config('app.timezone');
        $endOfVisitDay = Carbon::parse($visit->visit_date->toDateString(), $timezone)
            ->endOfDay()
            ->utc();

        return PatientVital::query()
            ->where('patient_id', $visit->patient_id)
            ->where('recorded_at', '<=', $endOfVisitDay)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Copies the latest known value of each mirrored field onto the patient.
     */
    private function syncPatient(Patient $patient): void
    {
        $latest = $this->latestValues($patient->id);

        $patient->forceFill(
            collect(self::PATIENT_COLUMNS)
                ->mapWithKeys(fn ($field) => [$field => $latest[$field]])
                ->all()
        )->save();
    }

    private function resolveVisit(Patient $patient, ?int $visitId): ?Visit
    {
        if ($visitId) {
            $visit = Visit::find($visitId);

            if (!$visit || $visit->patient_id !== $patient->id) {
                throw ValidationException::withMessages([
                    'visit_id' => [
                        'This visit does not belong to the patient.',
                    ],
                ]);
            }

            return $visit;
        }

        return $this->openVisit($patient);
    }

    /**
     * The visit the patient is in right now, if any — readings taken while
     * the doctor is consulting belong to that visit.
     */
    private function openVisit(Patient $patient): ?Visit
    {
        return Visit::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', self::OPEN_VISIT_STATUSES)
            ->latest('id')
            ->first();
    }

    private function findPatient(int $patientId): Patient
    {
        $patient = Patient::find($patientId);

        if (!$patient) {
            throw ValidationException::withMessages([
                'patient_id' => [
                    'Patient not found.',
                ],
            ]);
        }

        return $patient;
    }

    private function findVital(int $patientId, int $vitalId): PatientVital
    {
        $vital = PatientVital::query()
            ->where('patient_id', $patientId)
            ->find($vitalId);

        if (!$vital) {
            throw ValidationException::withMessages([
                'vital_id' => [
                    'Vitals record not found.',
                ],
            ]);
        }

        return $vital;
    }
}
