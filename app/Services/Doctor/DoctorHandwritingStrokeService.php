<?php

namespace App\Services\Doctor;

use App\Models\DoctorHandwritingSample;
use Illuminate\Support\Facades\Storage;
use Nexink\Proto\ProtoInkDocument;
use RuntimeException;

class DoctorHandwritingStrokeService
{
    /**
     * Decode the stored .ink.pb file into a Laravel array.
     */
    public function decode(DoctorHandwritingSample $sample): array
    {
        if (!$sample->ink_file_path) {
            throw new RuntimeException(
                'Doctor handwriting file not found.'
            );
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($sample->ink_file_path)) {
            throw new RuntimeException(
                'Doctor handwriting file not found.'
            );
        }

        $binary = $disk->get($sample->ink_file_path);

        if ($binary === '') {
            throw new RuntimeException(
                'Doctor handwriting file is empty.'
            );
        }

        $document = new ProtoInkDocument();

        try {
            $document->mergeFromString($binary);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Unable to decode doctor handwriting protobuf file.',
                previous: $e
            );
        }

        return [
            'sample_type' => $sample->sample_type,
            'prescription_id' => $sample->prescription_id,
            'strokes' => $this->convertStrokes($document),
        ];
    }

    /**
     * Convert protobuf strokes into normal PHP arrays.
     */
    private function convertStrokes(ProtoInkDocument $document): array
    {
        $strokes = [];

        foreach ($document->getStrokes() as $stroke) {
            $points = [];

            foreach ($stroke->getPoints() as $point) {
                $points[] = [
                    'x' => $point->getX(),
                    'y' => $point->getY(),
                    'pressure' => $point->getPressure(),
                    'tilt' => $point->getTilt(),
                    'orientation' => $point->getOrientation(),
                    'velocity' => $point->getVelocity(),
                    'timestamp' => $point->getTimestamp(),
                ];
            }

            $strokes[] = [
                'id' => $stroke->getId(),
                'points' => $points,
                'width' => $stroke->getWidth(),
                'color' => $stroke->getColor(),
                'is_eraser' => $stroke->getIsEraser(),
            ];
        }

        return $strokes;
    }
}
