<?php

declare(strict_types=1);

namespace App\Services\Basiq;

use App\Services\Basiq\BasiqService;
use App\Services\Shared\Authentication\SecretManager as SharedSecretManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConnectionManager
{
    private BasiqService $service;

    public function __construct(BasiqService $service)
    {
        $this->service = $service;
    }

    public function getBasiqUserId(): ?string
    {
        $fireflyUrl = SharedSecretManager::getBaseUrl();
        // Assuming single user for now, as firefly_user_id is nullable/not easily available without full OAuth flow info

        $record = DB::table('basiq_users')
            ->where('firefly_instance_url', $fireflyUrl)
            ->first();

        return $record ? $record->basiq_user_id : null;
    }

    public function createBasiqUser(string $email = null, string $mobile = null): string
    {
        Log::debug('Creating new Basiq user.');
        $user = $this->service->createUser($email, $mobile);
        $userId = $user['id'];

        $fireflyUrl = SharedSecretManager::getBaseUrl();

        DB::table('basiq_users')->updateOrInsert(
            ['firefly_instance_url' => $fireflyUrl],
            [
                'basiq_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Log::debug('Persisted Basiq user ID to database.', ['userId' => $userId]);

        return $userId;
    }
}
