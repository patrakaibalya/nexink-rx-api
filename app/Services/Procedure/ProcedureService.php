<?php

namespace App\Services\Procedure;

use App\Models\Procedure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcedureService
{
    public function show(int $procedureId): Procedure
    {
        $procedure = Procedure::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
                'documents',
            ])
            ->find($procedureId);

        if (!$procedure) {
            throw ValidationException::withMessages([
                'procedure_id' => [
                    'Procedure not found.',
                ],
            ]);
        }

        return $procedure;
    }

    public function update(
        int $procedureId,
        array $data
    ): Procedure {
        return DB::connection('doctor')->transaction(
            function () use ($procedureId, $data) {

                $procedure = Procedure::query()
                    ->lockForUpdate()
                    ->find($procedureId);

                if (!$procedure) {
                    throw ValidationException::withMessages([
                        'procedure_id' => [
                            'Procedure not found.',
                        ],
                    ]);
                }

                if ($procedure->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'procedure' => [
                            'A cancelled procedure cannot be updated.',
                        ],
                    ]);
                }

                $procedure->update([
                    'status' => $data['status']
                        ?? $procedure->status,

                    'notes' => array_key_exists('notes', $data)
                        ? $data['notes']
                        : $procedure->notes,
                ]);

                if (array_key_exists('items', $data)) {

                    $procedure->items()->delete();

                    foreach ($data['items'] as $index => $item) {
                        $procedure->items()->create([
                            'procedure_name' => $item['procedure_name'],
                            'instructions' => $item['instructions'] ?? null,
                            'sort_order' => $index,
                        ]);
                    }
                }

                return $procedure->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    public function delete(int $procedureId): void
    {
        $procedure = Procedure::query()
            ->lockForUpdate()
            ->find($procedureId);

        if (!$procedure) {
            throw ValidationException::withMessages([
                'procedure_id' => [
                    'Procedure not found.',
                ],
            ]);
        }

        if ($procedure->status === 'completed') {
            throw ValidationException::withMessages([
                'procedure' => [
                    'A completed procedure cannot be deleted.',
                ],
            ]);
        }

        $procedure->delete();
    }

    public function index(array $filters)
    {
        $query = Procedure::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
                'documents',
            ])
            ->latest('id');

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

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
                'procedure_date',
                $filters['date']
            );
        }

        return $query->paginate(
            $filters['per_page'] ?? 20
        );
    }

    public function complete(
        int $procedureId,
        array $data
    ): Procedure {
        return DB::connection('doctor')->transaction(
            function () use ($procedureId, $data) {

                $procedure = Procedure::query()
                    ->lockForUpdate()
                    ->with('items')
                    ->find($procedureId);

                if (!$procedure) {
                    throw ValidationException::withMessages([
                        'procedure_id' => [
                            'Procedure not found.',
                        ],
                    ]);
                }

                if ($procedure->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'procedure' => [
                            'A cancelled procedure cannot be completed.',
                        ],
                    ]);
                }

                if ($procedure->status === 'completed') {
                    throw ValidationException::withMessages([
                        'procedure' => [
                            'Procedure is already completed.',
                        ],
                    ]);
                }

                if ($procedure->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'A procedure must contain at least one item before completion.',
                        ],
                    ]);
                }

                foreach ($data['items'] as $itemData) {
                    $item = $procedure->items()
                        ->where('id', $itemData['id'])
                        ->first();

                    if (!$item) {
                        throw ValidationException::withMessages([
                            'items' => [
                                'Procedure item does not belong to this procedure.',
                            ],
                        ]);
                    }

                    $item->update([
                        'outcome_notes' => $itemData['outcome_notes'] ?? null,
                    ]);
                }

                $procedure->update([
                    'status' => 'completed',
                ]);

                return $procedure->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }
}
