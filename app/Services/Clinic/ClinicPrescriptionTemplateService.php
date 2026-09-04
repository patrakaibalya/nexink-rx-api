<?php

namespace App\Services\Clinic;

use App\Models\ClinicPrescriptionTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClinicPrescriptionTemplateService
{
    public function show(int $clinicId): ?ClinicPrescriptionTemplate
    {
        return ClinicPrescriptionTemplate::query()
            ->where('clinic_id', $clinicId)
            ->first();
    }

    public function upsert(
        int $clinicId,
        array $data,
        ?UploadedFile $headerImage,
        ?UploadedFile $footerImage
    ): ClinicPrescriptionTemplate {
        return DB::connection('doctor')->transaction(
            function () use ($clinicId, $data, $headerImage, $footerImage) {
                $template = ClinicPrescriptionTemplate::query()
                    ->where('clinic_id', $clinicId)
                    ->first();

                $headerImagePath = $template?->header_image_path;
                $footerImagePath = $template?->footer_image_path;

                if ($headerImage) {
                    $this->deleteIfExists($headerImagePath);
                    $headerImagePath = $this->storeImage(
                        clinicId: $clinicId,
                        band: 'header',
                        image: $headerImage
                    );
                }

                if ($footerImage) {
                    $this->deleteIfExists($footerImagePath);
                    $footerImagePath = $this->storeImage(
                        clinicId: $clinicId,
                        band: 'footer',
                        image: $footerImage
                    );
                }

                $attributes = [
                    'clinic_id' => $clinicId,

                    'page_width' => $data['page_width']
                        ?? $template?->page_width
                        ?? 794,

                    'page_height' => $data['page_height']
                        ?? $template?->page_height
                        ?? 1123,

                    'header_image_path' => $headerImagePath,

                    'header_width' => $data['header_width']
                        ?? $template?->header_width,

                    'header_height' => $data['header_height']
                        ?? $template?->header_height,

                    'footer_image_path' => $footerImagePath,

                    'footer_width' => $data['footer_width']
                        ?? $template?->footer_width,

                    'footer_height' => $data['footer_height']
                        ?? $template?->footer_height,
                ];

                if ($template) {
                    $template->update($attributes);

                    return $template->fresh();
                }

                return ClinicPrescriptionTemplate::create($attributes);
            }
        );
    }

    public function downloadHeaderImage(ClinicPrescriptionTemplate $template): BinaryFileResponse
    {
        return $this->downloadImage($template->header_image_path);
    }

    public function downloadFooterImage(ClinicPrescriptionTemplate $template): BinaryFileResponse
    {
        return $this->downloadImage($template->footer_image_path);
    }

    private function downloadImage(?string $path): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (!$path || !$disk->exists($path)) {
            abort(404, 'Prescription template image not found.');
        }

        return response()->file($disk->path($path));
    }

    private function storeImage(int $clinicId, string $band, UploadedFile $image): string
    {
        $path = "clinic-{$clinicId}/prescription-template";

        $filename = $band . '.' . $image->getClientOriginalExtension();

        $image->storeAs($path, $filename, 'local');

        return "{$path}/{$filename}";
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
