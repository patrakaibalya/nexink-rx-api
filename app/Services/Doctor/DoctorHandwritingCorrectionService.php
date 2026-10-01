<?php

namespace App\Services\Doctor;

use App\Models\DoctorHandwritingSample;
use App\Models\DoctorManualCorrection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DoctorHandwritingCorrectionService
{
    /**
     * Maps the categorized-JSON keys the Android app stores in
     * raw_recognized_text/final_corrected_text to the Clinical Dictionary
     * category the app files a learned correction under. Same mapping as the
     * app's PrescriptionReviewVm.dictionaryCategoryFor. The free-text sections
     * (Instruction, FollowUp, Other) are included so a reinstall restores them;
     * the app keeps them out of recognition matching on its own side.
     */
    private const CATEGORY_MAP = [
        'ADVISED MEDICATIONS' => 'Medicine',
        'INVESTIGATIONS' => 'Investigation',
        'PROCEDURES' => 'Procedure',
        'SYMPTOMS' => 'Symptom',
        'INSTRUCTIONS' => 'Instruction',
        'FOLLOW UP' => 'FollowUp',
        'OTHER' => 'Other',
    ];

    private const FREQUENCY_ABBREVIATIONS = [
        'od', 'bd', 'bid', 'tds', 'qid', 'hs', 'sos', 'prn', 'ac', 'af', 'stat',
    ];

    /**
     * Lists this doctor's word-level handwriting corrections, derived from
     * the raw-vs-final text already stored on every prescription handwriting
     * sample - no separate correction table needed. Diffing happens per
     * (category, word id) so a "corrected" word is one the doctor actually
     * changed during Review, matching how the Android app's own local
     * learning path (PrescriptionReviewVm.learnFromCorrections) decides
     * what counts as a correction.
     */
    public function listCorrections(int $doctorId, ?string $since = null): array
    {
        $query = DoctorHandwritingSample::query()
            ->where('doctor_id', $doctorId)
            ->where('sample_type', 'prescription')
            ->whereNotNull('raw_recognized_text')
            ->whereNotNull('final_corrected_text');

        if ($since) {
            $query->where('updated_at', '>=', Carbon::parse($since));
        }

        $corrections = [];

        $query->orderBy('updated_at')->chunk(50, function ($samples) use (&$corrections) {
            foreach ($samples as $sample) {
                $corrections = array_merge(
                    $corrections,
                    $this->diffSample($sample)
                );
            }
        });

        // Pairs the doctor added by hand on the Clinical Dictionary screen.
        $manualQuery = DoctorManualCorrection::query()->where('doctor_id', $doctorId);

        if ($since) {
            $manualQuery->where('updated_at', '>=', Carbon::parse($since));
        }

        foreach ($manualQuery->orderBy('updated_at')->get() as $manual) {
            $corrections[] = [
                'raw_recognized_text' => $manual->wrong_word,
                'final_corrected_text' => $manual->correct_word,
                'category' => $manual->category,
                'medicine_id' => null,
                'sample_id' => null,
                'source' => 'manual',
                'corrected_at' => optional($manual->updated_at)->toIso8601ZuluString(),
            ];
        }

        return [
            'corrections' => $corrections,
            'count' => count($corrections),
            'synced_at' => now()->toIso8601ZuluString(),
        ];
    }

    /**
     * Saves a pair added by hand on the app's Clinical Dictionary screen.
     * Saving the same pair again only refreshes updated_at.
     */
    public function saveManualCorrection(
        int $doctorId,
        string $wrongWord,
        string $correctWord,
        string $category
    ): DoctorManualCorrection {
        $correction = DoctorManualCorrection::query()->firstOrCreate([
            'doctor_id' => $doctorId,
            'wrong_word' => trim($wrongWord),
            'correct_word' => trim($correctWord),
            'category' => $category,
        ]);

        if (!$correction->wasRecentlyCreated) {
            $correction->touch();
        }

        return $correction;
    }

    /**
     * Removes manual pairs for one correct word. With $wrongWord only that
     * pair goes; without it, every pair of the word (the term was deleted).
     */
    public function deleteManualCorrections(
        int $doctorId,
        string $correctWord,
        string $category,
        ?string $wrongWord = null
    ): int {
        $query = DoctorManualCorrection::query()
            ->where('doctor_id', $doctorId)
            ->where('correct_word', trim($correctWord))
            ->where('category', $category);

        if ($wrongWord !== null) {
            $query->where('wrong_word', trim($wrongWord));
        }

        return $query->delete();
    }

    private function diffSample(DoctorHandwritingSample $sample): array
    {
        $raw = json_decode($sample->raw_recognized_text ?? '', true);
        $final = json_decode($sample->final_corrected_text ?? '', true);

        if (!is_array($raw) || !is_array($final)) {
            return [];
        }

        $correctedAt = optional($sample->updated_at)->toIso8601ZuluString();
        $entries = [];

        foreach (self::CATEGORY_MAP as $jsonCategory => $dictionaryCategory) {
            $rawWords = is_array($raw[$jsonCategory] ?? null) ? $raw[$jsonCategory] : [];
            $finalWords = is_array($final[$jsonCategory] ?? null) ? $final[$jsonCategory] : [];

            foreach ($finalWords as $wordId => $correctedText) {
                $rawText = $rawWords[$wordId] ?? null;
                $correctedText = trim((string) $correctedText);
                $rawText = $rawText === null ? null : trim((string) $rawText);

                if (
                    $rawText === null
                    || $rawText === ''
                    || $correctedText === ''
                    || mb_strlen($correctedText) < 2
                    || strcasecmp($rawText, $correctedText) === 0
                ) {
                    continue;
                }

                $category = $dictionaryCategory === 'Medicine'
                    ? $this->classifyMedicineWord($correctedText)
                    : $dictionaryCategory;

                $entries[] = [
                    'raw_recognized_text' => $rawText,
                    'final_corrected_text' => $correctedText,
                    'category' => $category,
                    'medicine_id' => null,
                    'sample_id' => $sample->id,
                    'source' => 'prescription',
                    'corrected_at' => $correctedAt,
                ];
            }
        }

        return $entries;
    }

    /**
     * Mirrors ClinicalKnowledgeBase.classifyMedicineLineWord on the Android
     * side, so a correction synced down lands in the same Dosage/Frequency/
     * Medicine bucket the app would have classified it into locally.
     */
    private function classifyMedicineWord(string $word): string
    {
        $clean = trim($word);

        if (
            preg_match('/^\d(-\d){1,2}$/', $clean) === 1
            || in_array(strtolower($clean), self::FREQUENCY_ABBREVIATIONS, true)
        ) {
            return 'Frequency';
        }

        if (preg_match('/^\d+(\.\d+)?\s?(mg|mcg|g|ml|iu|tsp|tbsp|drop|drops)$/i', $clean) === 1) {
            return 'Dosage';
        }

        return 'Medicine';
    }

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
