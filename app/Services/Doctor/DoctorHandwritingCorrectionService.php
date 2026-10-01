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
     * sample, plus the pairs added by hand (doctor_manual_corrections).
     * Diffing happens per (category, word id) so a "corrected" word is one
     * the doctor actually changed during Review, matching how the Android
     * app's own local learning path (PrescriptionReviewVm.learnFromCorrections)
     * decides what counts as a correction.
     *
     * Each wrong -> correct pair appears once, with occurrence_count = how
     * many saved Rx pages (plus 1 if also added by hand) contain it, counted
     * over all time. The app keeps the larger of its own count and this one,
     * so syncing again - or a correction it already learned locally - never
     * counts twice. With $since, only pairs seen again since then are sent.
     */
    public function listCorrections(int $doctorId, ?string $since = null): array
    {
        // Taken before reading, so a correction saved while this runs is
        // still >= the next sync's since and isn't missed.
        $syncedAt = now();
        $sinceAt = $since ? Carbon::parse($since) : null;

        $pairs = [];

        DoctorHandwritingSample::query()
            ->where('doctor_id', $doctorId)
            ->where('sample_type', 'prescription')
            ->whereNotNull('raw_recognized_text')
            ->whereNotNull('final_corrected_text')
            ->chunkById(50, function ($samples) use (&$pairs) {
                foreach ($samples as $sample) {
                    // One page counts a pair once, even if the word repeats on it.
                    $seenOnPage = [];
                    foreach ($this->diffSample($sample) as $entry) {
                        $key = $this->pairKey($entry);
                        if (isset($seenOnPage[$key])) {
                            continue;
                        }
                        $seenOnPage[$key] = true;
                        $this->addPair($pairs, $key, $entry, $sample->updated_at);
                    }
                }
            });

        // Pairs the doctor added by hand on the Clinical Dictionary screen.
        $manualRows = DoctorManualCorrection::query()
            ->where('doctor_id', $doctorId)
            ->get();

        foreach ($manualRows as $manual) {
            $entry = [
                'raw_recognized_text' => $manual->wrong_word,
                'final_corrected_text' => $manual->correct_word,
                'category' => $manual->category,
                'medicine_id' => null,
                'sample_id' => null,
                'source' => 'manual',
                'corrected_at' => optional($manual->updated_at)->toIso8601ZuluString(),
            ];
            $this->addPair($pairs, $this->pairKey($entry), $entry, $manual->updated_at);
        }

        $corrections = [];
        foreach ($pairs as $pair) {
            if ($sinceAt && (!$pair['latest'] || $pair['latest']->lt($sinceAt))) {
                continue;
            }
            unset($pair['latest']);
            $corrections[] = $pair;
        }

        usort(
            $corrections,
            fn ($a, $b) => strcmp((string) $a['corrected_at'], (string) $b['corrected_at'])
        );

        return [
            'corrections' => $corrections,
            'count' => count($corrections),
            'synced_at' => $syncedAt->toIso8601ZuluString(),
        ];
    }

    /**
     * Same pair = same wrong word (exact, as the app stores it), same
     * correct word (any case - the app's term lookup ignores case) and
     * same category.
     */
    private function pairKey(array $entry): string
    {
        return $entry['raw_recognized_text']
            . "\u{1F}" . mb_strtolower($entry['final_corrected_text'])
            . "\u{1F}" . $entry['category'];
    }

    /** Adds one occurrence; the newest occurrence decides the shown text, source and time. */
    private function addPair(array &$pairs, string $key, array $entry, ?Carbon $at): void
    {
        if (!isset($pairs[$key])) {
            $pairs[$key] = $entry + ['occurrence_count' => 1, 'latest' => $at];
            return;
        }

        $pairs[$key]['occurrence_count']++;

        $latest = $pairs[$key]['latest'];
        if ($at && (!$latest || $at->gt($latest))) {
            $count = $pairs[$key]['occurrence_count'];
            $pairs[$key] = $entry + ['occurrence_count' => $count, 'latest' => $at];
        }
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
     * The doctor renamed a word (or changed its category) on the Clinical
     * Dictionary screen: its manual pairs follow. A pair that already exists
     * under the new word is kept once, not duplicated.
     */
    public function renameManualCorrections(
        int $doctorId,
        string $oldWord,
        string $oldCategory,
        string $newWord,
        string $newCategory
    ): int {
        $rows = DoctorManualCorrection::query()
            ->where('doctor_id', $doctorId)
            ->where('correct_word', trim($oldWord))
            ->where('category', $oldCategory)
            ->get();

        foreach ($rows as $row) {
            $alreadyThere = DoctorManualCorrection::query()
                ->where('doctor_id', $doctorId)
                ->where('wrong_word', $row->wrong_word)
                ->where('correct_word', trim($newWord))
                ->where('category', $newCategory)
                ->where('id', '!=', $row->id)
                ->exists();

            if ($alreadyThere) {
                $row->delete();
            } else {
                $row->update([
                    'correct_word' => trim($newWord),
                    'category' => $newCategory,
                ]);
            }
        }

        return $rows->count();
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
