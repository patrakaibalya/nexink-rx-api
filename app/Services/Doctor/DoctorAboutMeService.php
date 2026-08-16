<?php

namespace App\Services\Doctor;

use App\Models\DoctorAboutMeSample;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorAboutMeService
{
    public function index(
        int $doctorId
    ): ?DoctorAboutMeSample {
        return DoctorAboutMeSample::query()
            ->where('doctor_id', $doctorId)
            ->first();
    }

    public function store(
        int $doctorId,
        array $data,
        ?UploadedFile $inkFile
    ): DoctorAboutMeSample {
        return DB::connection('doctor')->transaction(
            function () use (
                $doctorId,
                $data,
                $inkFile
            ) {
                $inkFilePath = null;

                if ($inkFile) {
                    $inkFilePath = $inkFile->storeAs(
                        "doctor-{$doctorId}",
                        'about_me.ink.pb',
                        'local'
                    );
                }

                return DoctorAboutMeSample::create([
                    'doctor_id' => $doctorId,

                    'tool_data' =>
                    $data['tool_data'] ?? null,

                    'raw_recognized_text' =>
                    $data['raw_recognized_text'] ?? null,

                    'final_corrected_text' =>
                    $data['final_corrected_text'] ?? null,

                    'ink_file_path' => $inkFilePath,

                    'qdrant_status' => 'pending',
                ]);
            }
        );
    }

    public function update(
        int $doctorId,
        array $data,
        ?UploadedFile $inkFile
    ): ?DoctorAboutMeSample {
        $sample = $this->index($doctorId);

        if (!$sample) {
            return null;
        }

        return DB::connection('doctor')->transaction(
            function () use (
                $doctorId,
                $data,
                $inkFile,
                $sample
            ) {
                $inkFilePath = $sample->ink_file_path;

                if ($inkFile) {
                    if (
                        $inkFilePath
                        && Storage::disk('local')->exists(
                            $inkFilePath
                        )
                    ) {
                        Storage::disk('local')->delete(
                            $inkFilePath
                        );
                    }

                    $inkFilePath = $inkFile->storeAs(
                        "doctor-{$doctorId}",
                        'about_me.ink.pb',
                        'local'
                    );
                }

                $sample->update([
                    'tool_data' =>
                    $data['tool_data'] ?? null,

                    'raw_recognized_text' =>
                    $data['raw_recognized_text'] ?? null,

                    'final_corrected_text' =>
                    $data['final_corrected_text'] ?? null,

                    'ink_file_path' => $inkFilePath,

                    'qdrant_status' => 'pending',
                ]);

                return $sample->fresh();
            }
        );
    }
}
