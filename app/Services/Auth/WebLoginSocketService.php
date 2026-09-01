<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WebLoginSocketService
{
    public function registerChannel(
        string $challenge,
        string $channelSecret
    ): void {
        $url = config(
            'services.socket.web_login_register_url'
        );

        $apiKey = config(
            'services.socket.api_key'
        );

        if (!$url) {
            throw new RuntimeException(
                'Socket web login register URL is not configured.'
            );
        }

        if (!$apiKey) {
            throw new RuntimeException(
                'Socket API key is not configured.'
            );
        }

        $response = Http::timeout(5)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
            ])
            ->post($url, [
                'challenge' => $challenge,
                'channel_secret' => $channelSecret,
            ]);

        $response->throw();
    }

    public function notifyApproved(
        string $challenge,
        string $handoffToken,
        string $handoffExpiresAt
    ): void {
        $url = config(
            'services.socket.web_login_approved_url'
        );

        $apiKey = config(
            'services.socket.api_key'
        );

        if (!$url) {
            throw new RuntimeException(
                'Socket web login approval URL is not configured.'
            );
        }

        if (!$apiKey) {
            throw new RuntimeException(
                'Socket API key is not configured.'
            );
        }

        $response = Http::timeout(5)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
            ])
            ->post($url, [
                'challenge' => $challenge,
                'handoff_token' => $handoffToken,
                'handoff_expires_at' => $handoffExpiresAt,
            ]);

        $response->throw();
    }
}
