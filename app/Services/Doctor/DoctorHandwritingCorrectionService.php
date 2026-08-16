<?php

namespace App\Services\Doctor;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DoctorHandwritingCorrectionService
{
    public function correct(
        int $doctorId,
        string $rawRecognizedText,
        int $limit = 5
    ): array {
        $webhookUrl = config(
            'services.n8n.doctor_handwriting_correction_webhook'
        );

        if (!$webhookUrl) {
            throw new RuntimeException(
                'n8n Doctor Handwriting Correction webhook URL is not configured.'
            );
        }

        $response = Http::timeout(60)
            ->acceptJson()
            ->post(
                $webhookUrl,
                [
                    'doctor_id' => $doctorId,
                    'raw_recognized_text' => $rawRecognizedText,
                    'limit' => $limit,
                ]
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'n8n handwriting correction request failed. Status: '
                . $response->status()
            );
        }

        $data = $response->json();

        if (
            !is_array($data)
            || empty($data['success'])
            || !isset($data['corrected_text'])
        ) {
            throw new RuntimeException(
                'Invalid response from n8n handwriting correction workflow.'
            );
        }

        return [
            'raw_recognized_text' => $rawRecognizedText,
            'corrected_text' => (string) $data['corrected_text'],
            'memory_count' => (int) (
                $data['memory_count'] ?? 0
            ),
        ];
    }
}
