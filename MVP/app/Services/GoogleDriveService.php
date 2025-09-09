<?php

namespace App\Services;

use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Support\Arr;


class GoogleDriveService
{
    public function getClientFor(User $user): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');

        $token = [
            'access_token'  => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'created'       => now()->subHour()->getTimestamp(),
            'expires_in'    => max(60, now()->diffInSeconds($user->google_token_expires_at, false) ?? 0),
        ];
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired() && filled($user->google_refresh_token)) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

            $user->forceFill([
                'google_access_token'     => Arr::get($newToken, 'access_token', $user->google_access_token),
                'google_token_expires_at' => now()->addSeconds((int) Arr::get($newToken, 'expires_in', 3600) - 60),
            ])->save();

            $client->setAccessToken(array_merge($token, $newToken));
        }

        return $client;
    }

    public function listFiles(User $user, string $query = '', int $limit = 50): array
    {
        $client = $this->getClientFor($user);
        $drive  = new Drive($client);

        $q = "trashed = false";
        if ($query !== '') {
            $q .= " and name contains '{$this->escapeQuery($query)}'";
        }

        $files = $drive->files->listFiles([
            'q'         => $q,
            'pageSize'  => $limit,
            'fields'    => 'files(id,name,mimeType,modifiedTime,webViewLink,iconLink)',
            'orderBy'   => 'modifiedTime desc',
            'supportsAllDrives' => false,
        ]);

        return collect($files->getFiles() ?? [])
            ->map(fn($f) => [
                'id'           => $f->getId(),
                'name'         => $f->getName(),
                'mimeType'     => $f->getMimeType(),
                'modifiedTime' => $f->getModifiedTime(),
                'webViewLink'  => $f->getWebViewLink(),
                'iconLink'     => $f->getIconLink(),
            ])->all();
    }

    private function escapeQuery(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
