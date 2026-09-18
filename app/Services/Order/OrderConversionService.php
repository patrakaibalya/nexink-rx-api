<?php

namespace App\Services\Order;

use App\Jobs\ConvertPrescriptionToOrderJob;
use App\Models\DoctorAccount;
use App\Models\GlobalInvestigationLibrary;
use App\Models\GlobalMedicineLibrary;
use App\Models\GlobalProcedureLibrary;
use App\Models\MedicineOrganization;
use App\Models\PrescriptionShare;
use App\Services\Investigation\InvestigationLibraryProvisioningService;
use App\Services\Medicine\MedicineLibraryProvisioningService;
use App\Services\Procedure\ProcedureLibraryProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderConversionService
{
    public function __construct(
        protected OrderDataProvisioningService $orderDataProvisioningService,
        protected MedicineLibraryProvisioningService $medicineLibraryProvisioningService,
        protected InvestigationLibraryProvisioningService $investigationLibraryProvisioningService,
        protected ProcedureLibraryProvisioningService $procedureLibraryProvisioningService
    ) {
    }

    /**
     * Kick off conversion of a shared prescription into a draft order.
     * The order row is created immediately in a 'converting' state and
     * the slow AI matching work is handed off to a queued job.
     */
    public function convert(PrescriptionShare $share): array
    {
        $organization = MedicineOrganization::query()->findOrFail($share->organization_id);

        $table = $this->orderDataProvisioningService->createForOrganization($organization->id);

        $alreadyConverted = DB::table($table)
            ->where('prescription_share_id', $share->id)
            ->exists();

        if ($alreadyConverted) {
            throw ValidationException::withMessages([
                'prescription_share_id' => ['This prescription has already been converted to an order.'],
            ]);
        }

        $orderId = DB::table($table)->insertGetId([
            'prescription_share_id' => $share->id,
            'order_ref_id' => $this->generateOrderRefId(),
            'patient_snapshot' => json_encode($share->patient_snapshot ?? []),
            'doctor_name' => $this->resolveDoctorName($share->doctor_id),
            'status' => 'converting',
            'items' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ConvertPrescriptionToOrderJob::dispatch($organization->id, $orderId, $share->id);

        return self::decodeOrderRow(DB::table($table)->where('id', $orderId)->first());
    }

    /**
     * DB::table() (query builder) returns json columns as raw strings,
     * unlike Eloquent casts — decode them before the row leaves the API.
     */
    public static function decodeOrderRow(?object $row): ?array
    {
        if (!$row) {
            return null;
        }

        $order = (array) $row;
        $order['patient_snapshot'] = json_decode($order['patient_snapshot'] ?? '{}', true) ?? [];
        $order['items'] = json_decode($order['items'] ?? '[]', true) ?? [];
        $order['ai_meta'] = isset($order['ai_meta']) ? json_decode($order['ai_meta'], true) : null;

        return $order;
    }

    /**
     * Build the flattened, catalog-matched item list to send to the AI
     * webhook. Public + static-ish helpers so the job can call it too
     * without re-instantiating share/organization lookups.
     */
    public function buildPrescribedItems(PrescriptionShare $share, MedicineOrganization $organization): array
    {
        $payload = $share->payload ?? [];

        $items = [];

        foreach ($payload['medicines'] ?? [] as $medicine) {
            $name = trim((string) ($medicine['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = [
                'item_type' => 'medicine',
                'name' => $name,
                'strength' => $medicine['strength'] ?? null,
                'route' => $medicine['route'] ?? null,
                'dosage' => $medicine['dosage'] ?? null,
                'frequency' => $medicine['frequency'] ?? null,
                'duration' => $medicine['duration'] ?? null,
                'instructions' => $medicine['instructions'] ?? null,
                'ai_describe' => $medicine['ai_describe'] ?? null,
                'confidence' => $medicine['confidence'] ?? null,
                'candidates' => $this->findCandidates($organization, 'medicine', $name),
            ];
        }

        foreach ($this->mergeInvestigations($payload['diagnoses'] ?? [], $payload['investigations'] ?? []) as $investigation) {
            $name = trim((string) ($investigation['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = [
                'item_type' => 'investigation',
                'name' => $name,
                'ai_describe' => $investigation['ai_describe'] ?? null,
                'confidence' => $investigation['confidence'] ?? null,
                'candidates' => $this->findCandidates($organization, 'investigation', $name),
            ];
        }

        foreach ($payload['procedures'] ?? [] as $procedure) {
            $name = trim((string) ($procedure['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = [
                'item_type' => 'procedure',
                'name' => $name,
                'ai_describe' => $procedure['ai_describe'] ?? null,
                'confidence' => $procedure['confidence'] ?? null,
                'candidates' => $this->findCandidates($organization, 'procedure', $name),
            ];
        }

        return $items;
    }

    /**
     * `diagnoses` and `investigations` in the prescription payload carry
     * the same lab-test items from two different extraction stages —
     * merge them by name, keeping whichever entry has real confidence/
     * description data.
     */
    private function mergeInvestigations(array $diagnoses, array $investigations): array
    {
        $merged = [];

        foreach ([$diagnoses, $investigations] as $list) {
            foreach ($list as $entry) {
                $name = trim((string) ($entry['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $key = strtolower($name);

                if (!isset($merged[$key])) {
                    $merged[$key] = $entry;
                    continue;
                }

                if (empty($merged[$key]['confidence']) && !empty($entry['confidence'])) {
                    $merged[$key]['confidence'] = $entry['confidence'];
                }

                if (empty($merged[$key]['ai_describe']) && !empty($entry['ai_describe'])) {
                    $merged[$key]['ai_describe'] = $entry['ai_describe'];
                }
            }
        }

        return array_values($merged);
    }

    /**
     * Search the organization's own catalog table first; only fall back
     * to the shared global library when the org table has no hits.
     */
    private function findCandidates(MedicineOrganization $organization, string $itemType, string $name): array
    {
        $searchTerm = $this->cleanSearchTerm($name);

        [$orgTable, $globalModelClass, $nameColumn] = match ($itemType) {
            'medicine' => [
                $this->medicineLibraryProvisioningService->getTableName($organization->id),
                GlobalMedicineLibrary::class,
                'medicine_name',
            ],
            'investigation' => [
                $this->investigationLibraryProvisioningService->getTableName($organization->id),
                GlobalInvestigationLibrary::class,
                'investigation_name',
            ],
            'procedure' => [
                $this->procedureLibraryProvisioningService->getTableName($organization->id),
                GlobalProcedureLibrary::class,
                'procedure_name',
            ],
        };

        if (Schema::hasTable($orgTable)) {
            $orgCandidates = DB::table($orgTable)
                ->where('is_active', true)
                ->where($nameColumn, 'like', "%{$searchTerm}%")
                ->limit(5)
                ->get()
                ->map(fn ($row) => $this->toCandidate($row, $nameColumn, 'org'))
                ->all();

            if (!empty($orgCandidates)) {
                return $orgCandidates;
            }
        }

        return $globalModelClass::query()
            ->where('is_active', true)
            ->where($nameColumn, 'like', "%{$searchTerm}%")
            ->limit(5)
            ->get()
            ->map(fn ($row) => $this->toCandidate($row, $nameColumn, 'global'))
            ->all();
    }

    private function toCandidate(object $row, string $nameColumn, string $source): array
    {
        return [
            'catalog_id' => $row->id,
            'name' => $row->{$nameColumn},
            'sale_price' => $row->sale_price,
            'unit' => $row->unit_type,
            'source' => $source,
        ];
    }

    /**
     * Strip common strength/dosage tokens (e.g. "650mg") so the LIKE
     * search matches on the base drug/test/procedure name.
     */
    private function cleanSearchTerm(string $name): string
    {
        $cleaned = preg_replace('/\d+\s?(mg|ml|mcg|g|iu)\b/i', '', $name);
        $cleaned = trim((string) $cleaned);

        return $cleaned !== '' ? $cleaned : $name;
    }

    private function resolveDoctorName(int $doctorId): ?string
    {
        return DoctorAccount::query()->find($doctorId)?->name;
    }

    private function generateOrderRefId(): string
    {
        return 'ORD-' . now()->format('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
