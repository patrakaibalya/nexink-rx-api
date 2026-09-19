<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionShare;
use App\Services\Order\OrderConversionService;
use App\Services\Order\OrderDataProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(
        Request $request,
        OrderDataProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService->createForOrganization($organization->id);

        $query = DB::table($table);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('order_ref_id', 'like', "%{$search}%")
                    ->orWhere('doctor_name', 'like', "%{$search}%")
                    ->orWhere('patient_snapshot', 'like', "%{$search}%");
            });
        }

        if ($request->filled('order_ref')) {
            $query->where('order_ref_id', 'like', '%' . $request->string('order_ref')->toString() . '%');
        }

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

        $orders = $query
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        $orders->getCollection()->transform(
            fn ($row) => OrderConversionService::decodeOrderRow($row)
        );

        return ApiResponse::success(
            message: 'Orders retrieved successfully.',
            data: [
                'orders' => $orders,
            ]
        );
    }

    public function show(
        Request $request,
        int $orderId,
        OrderDataProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService->getTableName($organization->id);

        $order = DB::table($table)->where('id', $orderId)->first();

        if (!$order) {
            return ApiResponse::notFound('Order not found.');
        }

        return ApiResponse::success(
            message: 'Order retrieved successfully.',
            data: [
                'order' => OrderConversionService::decodeOrderRow($order),
            ]
        );
    }

    public function convertToOrder(
        Request $request,
        int $shareId,
        OrderConversionService $conversionService
    ): JsonResponse {
        $organization = $request->user();

        $share = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->find($shareId);

        if (!$share) {
            return ApiResponse::notFound('Shared prescription not found.');
        }

        $order = $conversionService->convert($share);

        return ApiResponse::created(
            message: 'Order conversion started.',
            data: [
                'order' => $order,
            ]
        );
    }

    public function submit(
        Request $request,
        int $orderId,
        OrderDataProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService->getTableName($organization->id);

        $order = DB::table($table)->where('id', $orderId)->first();

        if (!$order) {
            return ApiResponse::notFound('Order not found.');
        }

        if ($order->status !== 'draft') {
            return ApiResponse::error(
                'Only draft orders can be submitted.',
                null,
                422
            );
        }

        $validated = $request->validate([
            'items' => ['sometimes', 'array'],
            'items.*.item_type' => ['required_with:items', 'string'],
            'items.*.catalog_id' => ['nullable', 'integer'],
            'items.*.catalog_source' => ['nullable', 'string'],
            'items.*.name' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.rate' => ['required_with:items', 'numeric', 'min:0'],
        ]);

        $update = [
            'status' => 'submitted',
            'updated_at' => now(),
        ];

        if (isset($validated['items'])) {
            $items = array_map(function (array $item) {
                $item['total'] = round(($item['quantity'] ?? 0) * ($item['rate'] ?? 0), 2);
                $item['match_status'] = $item['match_status'] ?? 'manual';

                return $item;
            }, $validated['items']);

            $update['items'] = json_encode($items);
            $update['grand_total'] = array_sum(array_column($items, 'total'));
        }

        DB::table($table)->where('id', $orderId)->update($update);

        return ApiResponse::success(
            message: 'Order submitted successfully.',
            data: [
                'order' => OrderConversionService::decodeOrderRow(
                    DB::table($table)->where('id', $orderId)->first()
                ),
            ]
        );
    }
}
