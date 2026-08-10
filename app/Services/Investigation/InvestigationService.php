<?php

namespace App\Services\Investigation;

use App\Models\Investigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestigationService
{
    public function show(int $investigationId): Investigation
    {
        $investigation = Investigation::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
            ])
            ->find($investigationId);

        if (!$investigation) {
            throw ValidationException::withMessages([
                'investigation_id' => [
                    'Investigation not found.',
                ],
            ]);
        }

        return $investigation;
    }

    public function update(
        int $investigationId,
        array $data
    ): Investigation {
        return DB::connection('doctor')->transaction(
            function () use ($investigationId, $data) {

                $investigation = Investigation::query()
                    ->lockForUpdate()
                    ->find($investigationId);

                if (!$investigation) {
                    throw ValidationException::withMessages([
                        'investigation_id' => [
                            'Investigation not found.',
                        ],
                    ]);
                }

                if ($investigation->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'investigation' => [
                            'A cancelled investigation cannot be updated.',
                        ],
                    ]);
                }

                $investigation->update([
                    'status' => $data['status']
                        ?? $investigation->status,

                    'notes' => array_key_exists('notes', $data)
                        ? $data['notes']
                        : $investigation->notes,
                ]);

                if (array_key_exists('items', $data)) {

                    $investigation->items()->delete();

                    foreach ($data['items'] as $index => $item) {
                        $investigation->items()->create([
                            'test_name' => $item['test_name'],
                            'test_type' => $item['test_type'],
                            'instructions' => $item['instructions'] ?? null,
                            'sort_order' => $index,
                        ]);
                    }
                }

                return $investigation->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                ]);
            }
        );
    }

    public function delete(int $investigationId): void
    {
        $investigation = Investigation::query()
            ->lockForUpdate()
            ->find($investigationId);

        if (!$investigation) {
            throw ValidationException::withMessages([
                'investigation_id' => [
                    'Investigation not found.',
                ],
            ]);
        }

        if ($investigation->status === 'completed') {
            throw ValidationException::withMessages([
                'investigation' => [
                    'A completed investigation cannot be deleted.',
                ],
            ]);
        }

        $investigation->delete();
    }

    public function index(array $filters)
    {
        $query = Investigation::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
            ])
            ->latest('id');

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (!empty($filters['visit_id'])) {
            $query->where('visit_id', $filters['visit_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate(
                'investigation_date',
                $filters['date']
            );
        }

        return $query->paginate(
            $filters['per_page'] ?? 20
        );
    }
}
