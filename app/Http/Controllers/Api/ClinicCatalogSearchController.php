<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\CatalogSearchRequest;
use App\Models\Clinic;
use App\Models\GlobalInvestigationLibrary;
use App\Models\GlobalMedicineLibrary;
use App\Models\GlobalProcedureLibrary;
use App\Support\ApiResponse;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Clinic Catalog Search
|--------------------------------------------------------------------------
|
| Each clinic picks one subscribed medicine organization as its
| active_pricing_organization_id (see ClinicController). These endpoints
| search that organization's medicine/investigation/procedure library
| first, and only fall back to the global catalog when the organization
| has no matching (or no) entries - unlike DoctorMedicineLibraryController,
| which searches every subscribed organization at once.
|
*/
class ClinicCatalogSearchController extends Controller
{
    public function medicines(int $clinicId, CatalogSearchRequest $request): JsonResponse
    {
        return $this->search(
            $clinicId,
            $request,
            tablePrefix: 'medicine_library_',
            globalModel: GlobalMedicineLibrary::class,
            nameColumns: ['medicine_name', 'generic_name'],
            resultKey: 'medicines',
            message: 'Medicines retrieved successfully.',
        );
    }

    public function investigations(int $clinicId, CatalogSearchRequest $request): JsonResponse
    {
        return $this->search(
            $clinicId,
            $request,
            tablePrefix: 'investigation_library_',
            globalModel: GlobalInvestigationLibrary::class,
            nameColumns: ['investigation_name'],
            resultKey: 'investigations',
            message: 'Investigations retrieved successfully.',
        );
    }

    public function procedures(int $clinicId, CatalogSearchRequest $request): JsonResponse
    {
        return $this->search(
            $clinicId,
            $request,
            tablePrefix: 'procedure_library_',
            globalModel: GlobalProcedureLibrary::class,
            nameColumns: ['procedure_name'],
            resultKey: 'procedures',
            message: 'Procedures retrieved successfully.',
        );
    }

    private function search(
        int $clinicId,
        CatalogSearchRequest $request,
        string $tablePrefix,
        string $globalModel,
        array $nameColumns,
        string $resultKey,
        string $message,
    ): JsonResponse {
        $clinic = Clinic::find($clinicId);

        if (!$clinic) {
            return ApiResponse::notFound('Clinic not found.');
        }

        $search = $request->filled('search')
            ? $request->string('search')->toString()
            : null;

        $organizationId = $clinic->active_pricing_organization_id;
        $items = collect();
        $source = null;

        /*
        |----------------------------------------------------------------
        | Clinic's active pricing organization library
        |----------------------------------------------------------------
        */
        $selectColumns = array_merge(['id'], $nameColumns);

        if ($organizationId) {
            $tableName = $tablePrefix . $organizationId;

            if (Schema::hasTable($tableName)) {
                $query = DB::table($tableName)
                    ->select($selectColumns)
                    ->where('is_active', true);

                $this->applySearch($query, $nameColumns, $search);

                $items = $query->orderBy($nameColumns[0])->get();
                $source = 'organization';
            }
        }

        /*
        |----------------------------------------------------------------
        | Fallback: global catalog
        |----------------------------------------------------------------
        */
        if ($items->isEmpty()) {
            $query = $globalModel::query()
                ->select($selectColumns)
                ->where('is_active', true);

            $this->applySearch($query, $nameColumns, $search);

            $items = $query->orderBy($nameColumns[0])->get();
            $source = 'global';
        }

        $perPage = $request->integer('per_page', 20);
        $page = $request->integer('page', 1);
        $total = $items->count();

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return ApiResponse::success(
            message: $message,
            data: [
                $resultKey => $paginator,
                'source' => $source,
                'organization_id' => $organizationId,
            ]
        );
    }

    private function applySearch(
        QueryBuilder|EloquentBuilder $query,
        array $columns,
        ?string $search
    ): void {
        if (!$search) {
            return;
        }

        $query->where(function ($query) use ($columns, $search) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $query->where($column, 'like', "%{$search}%");
                } else {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            }
        });
    }
}
