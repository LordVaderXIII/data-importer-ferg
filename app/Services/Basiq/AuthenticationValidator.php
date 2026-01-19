<?php

declare(strict_types=1);

namespace App\Services\Basiq;

use App\Services\Enums\AuthenticationStatus;
use App\Services\Basiq\Authentication\SecretManager;
use App\Services\Shared\Authentication\AuthenticationValidatorInterface;
use Illuminate\Support\Facades\Log;

class AuthenticationValidator implements AuthenticationValidatorInterface
{
    public function validate(): AuthenticationStatus
    {
        Log::debug(sprintf('Now at %s', __METHOD__));

        $key = SecretManager::getApiKey();

        if ('' === $key) {
            return AuthenticationStatus::NODATA;
        }

        // is there a valid access token?
        if (TokenManager::hasValidAccessToken()) {
            return AuthenticationStatus::AUTHENTICATED;
        }

        try {
            TokenManager::getNewToken();
        } catch (\Exception) {
            return AuthenticationStatus::ERROR;
        }

        return AuthenticationStatus::AUTHENTICATED;
    }

    public function getData(): array
    {
        return [
            'api_key' => SecretManager::getApiKey(),
        ];
    }

    public function setData(array $data): void
    {
        SecretManager::saveApiKey($data['api_key']);
    }
}
