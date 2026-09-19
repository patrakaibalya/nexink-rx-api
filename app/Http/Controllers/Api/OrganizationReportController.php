<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorAccount;
use App\Models\DoctorMedicineSubscription;
use App\Models\PrescriptionShare;
use App\Services\Order\OrderDataProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationReportController extends Controller
{
    /**
     * Doctors currently subscribed to this organization (approved and not
     * expired, same definition as the dashboard's subscriber count) —
     * feeds the "Doctor" filter dropdown on the reports screen.
     */
    public function doctors(Request $request): JsonResponse
    {
        $organization = $request->user();

        $doctorIds = DoctorMedicineSubscription::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->distinct()
            ->pluck('doctor_id');

        $doctors = DoctorAccount::query()
            ->whereIn('id', $doctorIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return ApiResponse::success(
            message: 'Doctors retrieved successfully.',
            data: [
                'doctors' => $doctors,
            ]
        );
    }

    /**
     * Prescribed medicines/investigations that the AI conversion could not
     * match to any catalog entry (`ai_meta.items[].match_status === 'unmatched'`)
     * — surfaced so the organization can see what needs adding to their
     * catalog, flattened across every order and paginated in PHP since the
     * data lives inside a per-order JSON blob rather than its own table.
     */
    public function unrecognisedMedicines(
        Request $request,
        OrderDataProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService->getTableName($organization->id);

        if (!Schema::hasTable($table)) {
            return ApiResponse::success(
                message: 'Unrecognised medicines retrieved successfully.',
                data: [
                    'items' => $this->emptyPage($request),
                ]
            );
        }

        $query = DB::table($table)->whereNotNull('ai_meta');

        if ($request->filled('doctor_id')) {
            $shareIds = PrescriptionShare::query()
                ->where('organization_id', $organization->id)
                ->where('doctor_id', $request->integer('doctor_id'))
                ->pluck('id');

            $query->whereIn('prescription_share_id', $shareIds);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date')->toString());
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date')->toString());
        }

        $orders = $query->orderByDesc('id')->get([
            'id',
            'order_ref_id',
            'patient_snapshot',
            'doctor_name',
            'ai_meta',
            'created_at',
        ]);

        $unrecognised = [];

        foreach ($orders as $order) {
            $aiMeta = json_decode($order->ai_meta ?? '{}', true) ?: [];
            $patientSnapshot = json_decode($order->patient_snapshot ?? '{}', true) ?: [];

            foreach ($aiMeta['items'] ?? [] as $item) {
                if (($item['match_status'] ?? null) !== 'unmatched') {
                    continue;
                }

                $unrecognised[] = [
                    'order_id' => $order->id,
                    'order_ref_id' => $order->order_ref_id,
                    'patient_name' => $patientSnapshot['name'] ?? null,
                    'doctor_name' => $order->doctor_name,
                    'item_type' => $item['item_type'] ?? null,
                    'prescribed_name' => $item['prescribed_name'] ?? null,
                    'created_at' => $order->created_at,
                ];
            }
        }

        return ApiResponse::success(
            message: 'Unrecognised medicines retrieved successfully.',
            data: [
                'items' => $this->paginateArray($unrecognised, $request),
            ]
        );
    }

    private function paginateArray(array $items, Request $request): LengthAwarePaginator
    {
        $perPage = $request->integer('per_page', 20);
        $page = $request->integer('page', 1);

        return new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function emptyPage(Request $request): LengthAwarePaginator
    {
        return $this->paginateArray([], $request);
    }
}
