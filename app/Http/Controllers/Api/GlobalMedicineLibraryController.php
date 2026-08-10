<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalMedicineLibrary\GlobalMedicineLibraryStoreRequest;
use App\Models\GlobalMedicineLibrary;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalMedicineLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GlobalMedicineLibrary::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('medicine_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $medicines = $query
            ->latest('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return ApiResponse::success(
            message: 'Global medicines retrieved successfully.',
            data: [
                'medicines' => $medicines,
            ]
        );
    }

    public function store(
        GlobalMedicineLibraryStoreRequest $request
    ): JsonResponse {
        $medicine = GlobalMedicineLibrary::create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Global medicine created successfully.',
            data: [
                'medicine' => $medicine,
            ]
        );
    }

    public function show(int $medicineId): JsonResponse
    {
        $medicine = GlobalMedicineLibrary::find(
            $medicineId
        );

        if (!$medicine) {
            return ApiResponse::notFound(
                'Global medicine not found.'
            );
        }

        return ApiResponse::success(
            message: 'Global medicine retrieved successfully.',
            data: [
                'medicine' => $medicine,
            ]
        );
    }

    public function update(
        GlobalMedicineLibraryStoreRequest $request,
        int $medicineId
    ): JsonResponse {
        $medicine = GlobalMedicineLibrary::find(
            $medicineId
        );

        if (!$medicine) {
            return ApiResponse::notFound(
                'Global medicine not found.'
            );
        }

        $medicine->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Global medicine updated successfully.',
            data: [
                'medicine' => $medicine->fresh(),
            ]
        );
    }

    public function destroy(int $medicineId): JsonResponse
    {
        $medicine = GlobalMedicineLibrary::find(
            $medicineId
        );

        if (!$medicine) {
            return ApiResponse::notFound(
                'Global medicine not found.'
            );
        }

        $medicine->delete();

        return ApiResponse::success(
            message: 'Global medicine deleted successfully.'
        );
    }
}
