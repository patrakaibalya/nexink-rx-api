<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\CorrectDoctorHandwritingRequest;
use App\Services\Doctor\DoctorHandwritingCorrectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorHandwritingCorrectionController extends Controller
{
    public function correct(
        CorrectDoctorHandwritingRequest $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $result = $doctorHandwritingCorrectionService->correct(
            doctorId: $doctor->id,
            rawRecognizedText: $request->validated(
                'raw_recognized_text'
            ),
            limit: $request->validated('limit') ?? 5
        );

        return ApiResponse::success(
            message: 'Handwriting corrected successfully.',
            data: $result
        );
    }

    /**
     * Bulk sync for the Android app's local Clinical Dictionary
     * (DictionaryManagementRepository.syncCorrectionsFromServer): every
     * word-level correction this doctor has ever confirmed, derived from
     * their stored handwriting samples rather than a live Qdrant lookup,
     * plus the pairs they added by hand (doctor_manual_corrections).
     */
    public function list(
        Request $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'since' => ['nullable', 'date'],
            ]
        )->validate();

        $result = $doctorHandwritingCorrectionService->listCorrections(
            doctorId: $doctor->id,
            since: $data['since'] ?? null
        );

        return ApiResponse::success(
            message: 'Doctor handwriting corrections retrieved successfully.',
            data: $result
        );
    }

    /**
     * Backs up a wrong -> correct pair the doctor added by hand on the app's
     * Clinical Dictionary screen, so a reinstall's sync restores it.
     */
    public function storeManual(
        Request $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->all(),
            [
                'wrong_word' => ['required', 'string', 'max:191'],
                'correct_word' => ['required', 'string', 'max:191'],
                'category' => ['required', 'string', 'max:50'],
            ]
        )->validate();

        $correction = $doctorHandwritingCorrectionService->saveManualCorrection(
            doctorId: $doctor->id,
            wrongWord: $data['wrong_word'],
            correctWord: $data['correct_word'],
            category: $data['category']
        );

        return ApiResponse::success(
            message: 'Manual correction saved successfully.',
            data: ['correction' => $correction]
        );
    }

    /**
     * The doctor renamed a word (or changed its category) in the app's
     * Clinical Dictionary; its manual pairs are moved to the new word.
     */
    public function renameManual(
        Request $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->all(),
            [
                'old_word' => ['required', 'string', 'max:191'],
                'old_category' => ['required', 'string', 'max:50'],
                'new_word' => ['required', 'string', 'max:191'],
                'new_category' => ['required', 'string', 'max:50'],
            ]
        )->validate();

        $renamed = $doctorHandwritingCorrectionService->renameManualCorrections(
            doctorId: $doctor->id,
            oldWord: $data['old_word'],
            oldCategory: $data['old_category'],
            newWord: $data['new_word'],
            newCategory: $data['new_category']
        );

        return ApiResponse::success(
            message: 'Manual corrections renamed successfully.',
            data: ['renamed' => $renamed]
        );
    }

    /**
     * Deletes one manual pair, or all of a word's manual pairs when
     * wrong_word is left out (the word was deleted from the dictionary).
     */
    public function destroyManual(
        Request $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'correct_word' => ['required', 'string', 'max:191'],
                'category' => ['required', 'string', 'max:50'],
                'wrong_word' => ['nullable', 'string', 'max:191'],
            ]
        )->validate();

        $deleted = $doctorHandwritingCorrectionService->deleteManualCorrections(
            doctorId: $doctor->id,
            correctWord: $data['correct_word'],
            category: $data['category'],
            wrongWord: $data['wrong_word'] ?? null
        );

        return ApiResponse::success(
            message: 'Manual correction deleted successfully.',
            data: ['deleted' => $deleted]
        );
    }
}
