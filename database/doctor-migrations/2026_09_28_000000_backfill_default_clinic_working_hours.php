<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Legacy Sunday rows used day_of_week 0; the PUT endpoint only ever
        // writes 7, so a 7 row is always the newer one.
        $clinicIdsWithIsoSunday = DB::table('clinic_working_hours')
            ->where('day_of_week', 7)
            ->pluck('clinic_id');

        DB::table('clinic_working_hours')
            ->where('day_of_week', 0)
            ->whereIn('clinic_id', $clinicIdsWithIsoSunday)
            ->delete();

        DB::table('clinic_working_hours')
            ->where('day_of_week', 0)
            ->update([
                'day_of_week' => 7,
            ]);

        $clinicIds = DB::table('clinics')
            ->pluck('id');

        foreach ($clinicIds as $clinicId) {
            $rows = collect(range(1, 7))
                ->map(function (int $day) use ($clinicId, $now) {
                    // ISO day of week: 7 = Sunday (closed by default)
                    $isClosed = $day === 7;

                    return [
                        'clinic_id' => $clinicId,
                        'day_of_week' => $day,
                        'opening_time' => $isClosed ? null : '09:00',
                        'closing_time' => $isClosed ? null : '18:00',
                        'is_closed' => $isClosed,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->all();

            // Unique (clinic_id, day_of_week) keeps any hours already set
            DB::table('clinic_working_hours')
                ->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Backfilled rows are indistinguishable from user-edited ones
    }
};
