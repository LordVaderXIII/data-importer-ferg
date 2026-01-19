<?php

declare(strict_types=1);

namespace App\Services\Basiq;

use Carbon\Carbon;
use App\Exceptions\ImporterErrorException;
use App\Services\Basiq\Authentication\SecretManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TokenManager
{
    public const string ACCESS_TOKEN = 'basiq_access_token';
    public const string ACCESS_EXPIRY = 'basiq_access_expiry';

    public static function getAccessToken(): string
    {
        self::validateToken();

        try {
            return (string) session()->get(self::ACCESS_TOKEN);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
    }

    public static function validateToken(): void
    {
        if (self::hasValidAccessToken()) {
            return;
        }

        self::getNewToken();
    }

    public static function hasValidAccessToken(): bool
    {
        if (!session()->has(self::ACCESS_TOKEN)) {
            Log::debug('No Basiq access token in session.');
            return false;
        }

        try {
            $expiry = (int) session()->get(self::ACCESS_EXPIRY, 0);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
            $expiry = 0;
        }

        // Buffer of 60 seconds
        return Carbon::now()->timestamp < ($expiry - 60);
    }

    public static function getNewToken(): void
    {
        Log::debug('Requesting new Basiq access token.');
        $apiKey = SecretManager::getApiKey();

        if (empty($apiKey)) {
            throw new ImporterErrorException('Basiq API Key is missing.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'basiq-version' => '3.0',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://au-api.basiq.io/token', [
            'scope' => 'CLIENT_ACCESS',
        ]);

        if ($response->failed()) {
            Log::error('Failed to get Basiq token', ['body' => $response->body()]);
            throw new ImporterErrorException('Failed to retrieve Basiq access token.');
        }

        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in']; // seconds

        session()->put(self::ACCESS_TOKEN, $token);
        session()->put(self::ACCESS_EXPIRY, Carbon::now()->timestamp + $expiresIn);

        Log::debug('Successfully retrieved and stored Basiq access token.');
    }
}
