<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\WebLoginApproveRequest;
use App\Http\Requests\Auth\WebLoginCompleteRequest;
use App\Services\Auth\WebLoginChallengeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Auth\StatefulGuard;

class WebLoginController extends Controller
{
    public function challenge(
        WebLoginChallengeService $webLoginChallengeService
    ): JsonResponse {

        $result = $webLoginChallengeService->create();

        $challenge = $result['challenge'];
        $channelSecret = $result['channel_secret'];

        return ApiResponse::created(
            message: 'Web login challenge created successfully.',
            data: [
                'challenge' => $challenge->challenge,

                'channel_secret' => $channelSecret,

                'qr_data' => $challenge->challenge,

                'expires_at' => $challenge->expires_at,
            ]
        );
    }

    public function approve(
        WebLoginApproveRequest $request,
        WebLoginChallengeService $webLoginChallengeService
    ): JsonResponse {
        $result = $webLoginChallengeService->approve(
            $request->validated('challenge'),
            $request->user()->id
        );

        $challenge = $result['challenge'];

        return ApiResponse::success(
            message: 'Web login approved successfully.',
            data: [
                'challenge' => [
                    'id' => $challenge->id,
                    'status' => $challenge->status,
                    'doctor_id' => $challenge->doctor_id,
                    'approved_at' => $challenge->approved_at,
                    'handoff_expires_at' =>
                    $challenge->handoff_expires_at,
                ],

                'handoff_token' =>
                $result['handoff_token'],
            ]
        );
    }

    public function complete(
        WebLoginCompleteRequest $request,
        WebLoginChallengeService $webLoginChallengeService
    ): JsonResponse {
        $challenge = $webLoginChallengeService->complete(
            $request->validated('handoff_token')
        );

        $doctor = \App\Models\DoctorAccount::query()
            ->find($challenge->doctor_id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor account not found.',
            ], 422);
        }

        /** @var StatefulGuard $guard */
        $guard = auth()->guard('doctor_web');

        $guard->login($doctor);

        $request->session()->regenerate();

        return ApiResponse::success(
            message: 'Web login completed successfully.',
            data: [
                'doctor' => [
                    'id' => $doctor->id,
                ],
            ]
        );
    }
}
