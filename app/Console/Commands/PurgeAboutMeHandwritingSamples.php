<?php

namespace App\Console\Commands;

use App\Models\DoctorDatabase;
use App\Models\DoctorHandwritingSample;
use App\Support\DoctorTenantConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeAboutMeHandwritingSamples extends Command
{
    protected $signature = 'doctor:purge-about-me-samples
        {doctor_id? : Purge a single doctor by ID}
        {--all : Purge every active doctor database}
        {--force : Actually delete. Without this flag the command only reports what it would delete.}';

    protected $description = 'Delete legacy about_me handwriting samples (rows + ink files) '
        . 'from doctor tenant databases now that about_me is no longer supported';

    public function handle(): int
    {
        $doctorId = $this->argument('doctor_id');
        $all = (bool) $this->option('all');
        $force = (bool) $this->option('force');

        if (!$doctorId && !$all) {
            $this->error('Pass a doctor_id or --all.');

            return self::FAILURE;
        }

        $query = DoctorDatabase::query()->where('status', 'active');

        if ($doctorId) {
            $query->where('doctor_id', (int) $doctorId);
        }

        $doctorDatabases = $query->get();

        if ($doctorDatabases->isEmpty()) {
            $this->error('No matching active doctor database found.');

            return self::FAILURE;
        }

        if (!$force) {
            $this->warn('Dry run (no --force passed) — nothing will be deleted.');
        }

        $totalRows = 0;
        $totalFiles = 0;

        foreach ($doctorDatabases as $doctorDatabase) {
            if (!DoctorTenantConnector::connect($doctorDatabase->doctor_id)) {
                $this->warn(
                    "Doctor {$doctorDatabase->doctor_id}: could not connect, skipped."
                );

                continue;
            }

            $samples = DoctorHandwritingSample::query()
                ->where('sample_type', 'about_me')
                ->get();

            if ($samples->isEmpty()) {
                continue;
            }

            $fileCount = 0;

            foreach ($samples as $sample) {
                if (
                    $sample->ink_file_path
                    && Storage::disk('local')->exists($sample->ink_file_path)
                ) {
                    $fileCount++;

                    if ($force) {
                        Storage::disk('local')->delete($sample->ink_file_path);
                    }
                }
            }

            if ($force) {
                DoctorHandwritingSample::query()
                    ->where('sample_type', 'about_me')
                    ->delete();
            }

            $this->line(sprintf(
                'Doctor %d: %s %d about_me sample(s), %d file(s).',
                $doctorDatabase->doctor_id,
                $force ? 'purged' : 'found',
                $samples->count(),
                $fileCount
            ));

            $totalRows += $samples->count();
            $totalFiles += $fileCount;
        }

        $this->info(sprintf(
            '%s: %d sample row(s), %d file(s) across %d doctor database(s).',
            $force ? 'Purged' : 'Would purge',
            $totalRows,
            $totalFiles,
            $doctorDatabases->count()
        ));

        return self::SUCCESS;
    }
}
