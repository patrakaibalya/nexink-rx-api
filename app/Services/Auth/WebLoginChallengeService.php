<?php

namespace App\Services\Auth;

use App\Models\WebLoginChallenge;
use App\Services\Auth\WebLoginSocketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class WebLoginChallengeService
{
    private const CHALLENGE_TTL_SECONDS = 60;

    private const HANDOFF_TTL_SECONDS = 30;

    public function __construct(
        private WebLoginSocketService $webLoginSocketService
    ) {}

    /**
     * Create a web login challenge and register
     * its temporary Socket.IO channel.
     *
     * Returns:
     * [
     *     'challenge' => WebLoginChallenge,
     *     'channel_secret' => string,
     * ]
     */

    public function create(): array
    {
        $challenge = bin2hex(random_bytes(48));

        $channelSecret = bin2hex(random_bytes(32));

        $loginChallenge = WebLoginChallenge::create([
            'challenge' => $challenge,
            'doctor_id' => null,
            'status' => 'waiting',
            'expires_at' => now()->addSeconds(
                self::CHALLENGE_TTL_SECONDS
            ),
            'approved_at' => null,
            'consumed_at' => null,
            'handoff_hash' => null,
            'handoff_expires_at' => null,
        ]);

        $this->webLoginSocketService->registerChannel(
            $challenge,
            $channelSecret
        );

        return [
            'challenge' => $loginChallenge,
            'channel_secret' => $channelSecret,
        ];
    }

    /**
     * Approve a browser login challenge from
     * an authenticated Android doctor.
     *
     * Returns:
     * [
     *     'challenge' => WebLoginChallenge,
     *     'handoff_token' => string,
     * ]
     */
    public function approve(
        string $challenge,
        int $doctorId
    ): array {
        return DB::transaction(function () use (
            $challenge,
            $doctorId
        ) {
            $loginChallenge = WebLoginChallenge::query()
                ->lockForUpdate()
                ->where('challenge', $challenge)
                ->first();

            if (!$loginChallenge) {
                throw ValidationException::withMessages([
                    'challenge' => [
                        'Invalid web login challenge.',
                    ],
                ]);
            }

            if ($loginChallenge->status !== 'waiting') {
                throw ValidationException::withMessages([
                    'challenge' => [
                        'Web login challenge is no longer available.',
                    ],
                ]);
            }

            if ($loginChallenge->expires_at->lte(now())) {
                $loginChallenge->update([
                    'status' => 'expired',
                ]);

                throw ValidationException::withMessages([
                    'challenge' => [
                        'Web login challenge has expired.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Generate one-time handoff token
            |--------------------------------------------------------------------------
            */

            $handoffToken = bin2hex(
                random_bytes(48)
            );

            $handoffHash = hash(
                'sha256',
                $handoffToken
            );

            $handoffExpiresAt = now()->addSeconds(
                self::HANDOFF_TTL_SECONDS
            );

            /*
            |--------------------------------------------------------------------------
            | Approve challenge
            |--------------------------------------------------------------------------
            */

            $loginChallenge->update([
                'doctor_id' => $doctorId,
                'status' => 'approved',
                'approved_at' => now(),
                'handoff_hash' => $handoffHash,
                'handoff_expires_at' => $handoffExpiresAt,
            ]);

            $loginChallenge->refresh();

            $this->webLoginSocketService->notifyApproved(
                $loginChallenge->challenge,
                $handoffToken,
                $loginChallenge->handoff_expires_at->toIso8601String()
            );

            return [
                'challenge' => $loginChallenge,
                'handoff_token' => $handoffToken,
            ];
        });
    }


    public function complete(
        string $handoffToken
    ): WebLoginChallenge {
        return DB::transaction(function () use ($handoffToken) {

            $handoffHash = hash(
                'sha256',
                $handoffToken
            );

            $loginChallenge = WebLoginChallenge::query()
                ->lockForUpdate()
                ->where('handoff_hash', $handoffHash)
                ->first();

            if (!$loginChallenge) {
                throw ValidationException::withMessages([
                    'handoff_token' => [
                        'Invalid web login handoff.',
                    ],
                ]);
            }

            if ($loginChallenge->status !== 'approved') {
                throw ValidationException::withMessages([
                    'handoff_token' => [
                        'Web login handoff is no longer available.',
                    ],
                ]);
            }

            if ($loginChallenge->consumed_at !== null) {
                throw ValidationException::withMessages([
                    'handoff_token' => [
                        'Web login handoff has already been consumed.',
                    ],
                ]);
            }

            if (
                !$loginChallenge->handoff_expires_at ||
                $loginChallenge->handoff_expires_at->lte(now())
            ) {
                throw ValidationException::withMessages([
                    'handoff_token' => [
                        'Web login handoff has expired.',
                    ],
                ]);
            }

            $loginChallenge->update([
                'status' => 'consumed',
                'consumed_at' => now(),
            ]);

            return $loginChallenge->fresh();
        });
    }
}
