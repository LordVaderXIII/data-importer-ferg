<?php

declare(strict_types=1);

namespace App\Services\Basiq\Authentication;

use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SecretManager
{
    public const string BASIQ_API_KEY = 'basiq_api_key';

    public static function getApiKey(): string
    {
        if (!self::hasApiKey()) {
            Log::debug('No Basiq API Key in session, will return config variable.');
            return (string) config('basiq.api_key');
        }

        try {
            $key = (string) session()->get(self::BASIQ_API_KEY);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
            $key = '';
        }

        return $key;
    }

    private static function hasApiKey(): bool
    {
        try {
            $key = (string) session()->get(self::BASIQ_API_KEY);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
            $key = '';
        }

        return '' !== $key;
    }

    public static function saveApiKey(string $key): void
    {
        session()->put(self::BASIQ_API_KEY, $key);
    }
}
