<?php

namespace App\Services\Investigation;

use App\Models\Investigation;
use App\Models\InvestigationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvestigationDocumentService
{
    public function store(int $investigationId, UploadedFile $document): Investigation
    {
        return DB::connection('doctor')->transaction(
            function () use ($investigationId, $document) {
                $investigation = Investigation::find($investigationId);

                if (!$investigation) {
                    throw ValidationException::withMessages([
                        'investigation_id' => [
                            'Investigation not found.',
                        ],
                    ]);
                }

                $directory = "investigation-{$investigationId}/documents";
                $filename = uniqid('doc_', true) . '.' . $document->getClientOriginalExtension();

                $document->storeAs($directory, $filename, 'local');

                InvestigationDocument::create([
                    'investigation_id' => $investigationId,
                    'file_path' => "{$directory}/{$filename}",
                    'original_name' => $document->getClientOriginalName(),
                    'mime_type' => $document->getClientMimeType(),
                    'size' => $document->getSize(),
                ]);

                return $investigation->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    public function destroy(int $investigationId, int $documentId): Investigation
    {
        return DB::connection('doctor')->transaction(
            function () use ($investigationId, $documentId) {
                $investigation = Investigation::find($investigationId);

                if (!$investigation) {
                    throw ValidationException::withMessages([
                        'investigation_id' => [
                            'Investigation not found.',
                        ],
                    ]);
                }

                $document = InvestigationDocument::query()
                    ->where('investigation_id', $investigationId)
                    ->where('id', $documentId)
                    ->first();

                if (!$document) {
                    throw ValidationException::withMessages([
                        'document_id' => [
                            'Investigation document not found.',
                        ],
                    ]);
                }

                if (Storage::disk('local')->exists($document->file_path)) {
                    Storage::disk('local')->delete($document->file_path);
                }

                $document->delete();

                return $investigation->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    /**
     * Swap the file behind an existing document (e.g. a corrected lab
     * report) while keeping the same document row, then remove the old
     * file from disk once the new one is saved.
     */
    public function replace(int $investigationId, int $documentId, UploadedFile $file): Investigation
    {
        return DB::connection('doctor')->transaction(
            function () use ($investigationId, $documentId, $file) {
                $investigation = Investigation::find($investigationId);

                if (!$investigation) {
                    throw ValidationException::withMessages([
                        'investigation_id' => [
                            'Investigation not found.',
                        ],
                    ]);
                }

                $document = $this->find($investigationId, $documentId);
                $oldPath = $document->file_path;

                $directory = "investigation-{$investigationId}/documents";
                $filename = uniqid('doc_', true) . '.' . $file->getClientOriginalExtension();

                $file->storeAs($directory, $filename, 'local');

                $document->update([
                    'file_path' => "{$directory}/{$filename}",
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                if ($oldPath !== $document->file_path && Storage::disk('local')->exists($oldPath)) {
                    Storage::disk('local')->delete($oldPath);
                }

                return $investigation->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    public function find(int $investigationId, int $documentId): InvestigationDocument
    {
        $document = InvestigationDocument::query()
            ->where('investigation_id', $investigationId)
            ->where('id', $documentId)
            ->first();

        if (!$document) {
            throw ValidationException::withMessages([
                'document_id' => [
                    'Investigation document not found.',
                ],
            ]);
        }

        return $document;
    }

    public function download(InvestigationDocument $document): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($document->file_path)) {
            abort(404, 'Investigation document file not found.');
        }

        return response()->file(
            $disk->path($document->file_path),
            [
                'Content-Type' => $document->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
            ]
        );
    }
}
