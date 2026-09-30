<?php

namespace App\Services\Procedure;

use App\Models\Procedure;
use App\Models\ProcedureDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProcedureDocumentService
{
    public function store(int $procedureId, UploadedFile $document): Procedure
    {
        return DB::connection('doctor')->transaction(
            function () use ($procedureId, $document) {
                $procedure = Procedure::find($procedureId);

                if (!$procedure) {
                    throw ValidationException::withMessages([
                        'procedure_id' => [
                            'Procedure not found.',
                        ],
                    ]);
                }

                $directory = "procedure-{$procedureId}/documents";
                $filename = uniqid('doc_', true) . '.' . $document->getClientOriginalExtension();

                $document->storeAs($directory, $filename, 'local');

                ProcedureDocument::create([
                    'procedure_id' => $procedureId,
                    'file_path' => "{$directory}/{$filename}",
                    'original_name' => $document->getClientOriginalName(),
                    'mime_type' => $document->getClientMimeType(),
                    'size' => $document->getSize(),
                ]);

                return $procedure->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    public function destroy(int $procedureId, int $documentId): Procedure
    {
        return DB::connection('doctor')->transaction(
            function () use ($procedureId, $documentId) {
                $procedure = Procedure::find($procedureId);

                if (!$procedure) {
                    throw ValidationException::withMessages([
                        'procedure_id' => [
                            'Procedure not found.',
                        ],
                    ]);
                }

                $document = ProcedureDocument::query()
                    ->where('procedure_id', $procedureId)
                    ->where('id', $documentId)
                    ->first();

                if (!$document) {
                    throw ValidationException::withMessages([
                        'document_id' => [
                            'Procedure document not found.',
                        ],
                    ]);
                }

                if (Storage::disk('local')->exists($document->file_path)) {
                    Storage::disk('local')->delete($document->file_path);
                }

                $document->delete();

                return $procedure->fresh([
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
    public function replace(int $procedureId, int $documentId, UploadedFile $file): Procedure
    {
        return DB::connection('doctor')->transaction(
            function () use ($procedureId, $documentId, $file) {
                $procedure = Procedure::find($procedureId);

                if (!$procedure) {
                    throw ValidationException::withMessages([
                        'procedure_id' => [
                            'Procedure not found.',
                        ],
                    ]);
                }

                $document = $this->find($procedureId, $documentId);
                $oldPath = $document->file_path;

                $directory = "procedure-{$procedureId}/documents";
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

                return $procedure->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                    'documents',
                ]);
            }
        );
    }

    public function find(int $procedureId, int $documentId): ProcedureDocument
    {
        $document = ProcedureDocument::query()
            ->where('procedure_id', $procedureId)
            ->where('id', $documentId)
            ->first();

        if (!$document) {
            throw ValidationException::withMessages([
                'document_id' => [
                    'Procedure document not found.',
                ],
            ]);
        }

        return $document;
    }

    public function download(ProcedureDocument $document): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($document->file_path)) {
            abort(404, 'Procedure document file not found.');
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
