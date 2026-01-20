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

        try {
            $record = DB::table('basiq_users')
                ->where('firefly_instance_url', $fireflyUrl)
                ->first();

            return $record ? $record->basiq_user_id : null;
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if the error is due to missing table
            if (str_contains($e->getMessage(), 'no such table: basiq_users')) {
                Log::warning('Basiq users table missing. Migration may not have run.');
                return null;
            }
            throw $e;
        }
    }

    public function createBasiqUser(string $email = null, string $mobile = null): string
    {
        Log::debug('Creating new Basiq user.');
        $user = $this->service->createUser($email, $mobile);
        $userId = $user['id'];

        $fireflyUrl = SharedSecretManager::getBaseUrl();

        // Ensure table exists before inserting?
        // We can't easily run migrations here.
        // If the table is missing, this will throw.
        // But getBasiqUserId will return null, so the controller will call this method.

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
