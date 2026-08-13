<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicineLibraryRequest;
use App\Models\DoctorMedicineSubscription;
use App\Models\GlobalMedicineLibrary;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorMedicineLibraryController extends Controller
{
    public function index(
        MedicineLibraryRequest $request
    ): JsonResponse {
        $doctor = $request->user();
        $source = $request->string('source')->toString();

        $subscriptionsQuery = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('organization');

        /*
    |--------------------------------------------------------------------------
    | Organization Filter
    |--------------------------------------------------------------------------
    |
    | Only allow organizations for which this doctor has
    | an approved and non-expired subscription.
    |
    */
        if ($request->filled('organization_id')) {
            $subscriptionsQuery->where(
                'organization_id',
                $request->integer('organization_id')
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Favorites Filter
    |--------------------------------------------------------------------------
    |
    | is_favorite belongs to the doctor's organization subscription.
    | Therefore favorites_only means organizations marked as favorite.
    |
    */
        if ($request->boolean('favorites_only')) {
            $subscriptionsQuery->where(
                'is_favorite',
                true
            );
        }

        $subscriptions = $subscriptionsQuery->get();

        $medicines = collect();


        /*
    |--------------------------------------------------------------------------
    | Global Medicine Library
    |--------------------------------------------------------------------------
    |
    | Global medicines are available to every doctor unless
    | a specific organization is requested.
    */
        if (
            !$request->filled('organization_id')
            && $source !== 'organization'
        ) {

            $globalQuery = GlobalMedicineLibrary::query()
                ->where('is_active', true);


            if ($request->filled('search')) {
                $search = $request
                    ->string('search')
                    ->toString();

                $globalQuery->where(function ($query) use ($search) {
                    $query
                        ->where(
                            'medicine_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'generic_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'manufacturer',
                            'like',
                            "%{$search}%"
                        );
                });
            }


            $globalMedicines = $globalQuery
                ->orderBy('medicine_name')
                ->get();


            foreach ($globalMedicines as $medicine) {
                $medicines->push([
                    'id' => $medicine->id,
                    'medicine_name' => $medicine->medicine_name,
                    'generic_name' => $medicine->generic_name,
                    'composition' => $medicine->composition,
                    'strength' => $medicine->strength,
                    'dosage_form' => $medicine->dosage_form,
                    'manufacturer' => $medicine->manufacturer,
                    'description' => $medicine->description,
                    'is_active' => (bool) $medicine->is_active,

                    'source' => 'global',

                    'organization' => null,
                ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Organization Medicine Libraries
    |--------------------------------------------------------------------------
    */
        if ($source !== 'global') {
            foreach ($subscriptions as $subscription) {
                $tableName = 'medicine_library_' . $subscription->organization_id;

                if (!Schema::hasTable($tableName)) {
                    continue;
                }

                $query = DB::table($tableName)
                    ->where('is_active', true);



                /*
    |--------------------------------------------------------------------------
    | Medicine Search
    |--------------------------------------------------------------------------
    */


                if ($request->filled('search')) {
                    $search = $request
                        ->string('search')
                        ->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'medicine_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'generic_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'manufacturer',
                                'like',
                                "%{$search}%"
                            );
                    });
                }

                $organizationMedicines = $query
                    ->orderBy('medicine_name')
                    ->get();

                foreach ($organizationMedicines as $medicine) {
                    $medicines->push([
                        'id' => $medicine->id,
                        'medicine_name' => $medicine->medicine_name,
                        'generic_name' => $medicine->generic_name,
                        'composition' => $medicine->composition,
                        'strength' => $medicine->strength,
                        'dosage_form' => $medicine->dosage_form,
                        'manufacturer' => $medicine->manufacturer,
                        'description' => $medicine->description,
                        'is_active' => (bool) $medicine->is_active,

                        'source' => 'organization',

                        'organization' => [
                            'id' => $subscription->organization->id,
                            'organization_name' =>
                            $subscription->organization->organization_name,
                            'is_favorite' => (bool) $subscription->is_favorite,
                        ],
                    ]);
                }
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $perPage = $request->integer(
            'per_page',
            20
        );

        $page = $request->integer(
            'page',
            1
        );

        $total = $medicines->count();

        $items = $medicines
            ->forPage($page, $perPage)
            ->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return ApiResponse::success(
            message: 'My medicine library retrieved successfully.',
            data: [
                'medicines' => $paginator,
            ]
        );
    }
}
