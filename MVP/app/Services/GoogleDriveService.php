<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class GoogleDriveService
{
    public function getClientFor(User $user): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');

        $token = [
            'access_token'  => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
            'created'       => now()->subHour()->getTimestamp(),
            'expires_in'    => max(60, now()->diffInSeconds($user->google_token_expires_at, false) ?? 0),
        ];
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired() && filled($user->google_refresh_token)) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

            $user->forceFill([
                'google_token'     => Arr::get($newToken, 'access_token', $user->google_token),
                'google_token_expires_in' => now()->addSeconds((int) Arr::get($newToken, 'expires_in', 3600) - 60),
            ])->save();

            $client->setAccessToken(array_merge($token, $newToken));
        }

        return $client;
    }

    public function listFiles(User $user, string $search = '', int $limit = 50, ?string $folderId = null, string $orderBy = 'name asc'): array {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            $query = [];

            $query[] = "mimeType = 'application/vnd.google-apps.spreadsheet'";
            $query[] = "trashed = false";

            if (!empty($search)) {
                $query[] = "name contains '{$search}'";
            }

            $queryString = implode(' and ', $query);

            $cacheKey = "drive_sheets_" . $user->id . "_" . md5($queryString . $orderBy . $limit);

            return Cache::remember($cacheKey, 300, function () use ($service, $queryString, $orderBy, $limit) {
                try {
                    $response = $service->files->listFiles([
                        'q' => $queryString,
                        'orderBy' => $orderBy,
                        'pageSize' => $limit,
                        'fields' => 'files(id,name,mimeType,size,modifiedTime,webViewLink,iconLink,parents,permissions),nextPageToken',
                        'supportsAllDrives' => true,
                        'includeItemsFromAllDrives' => true,
                    ]);

                    return array_map(fn($file) => [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'modifiedTime' => $file->getModifiedTime(),
                        'webViewLink' => $file->getWebViewLink(),
                        'iconLink' => $file->getIconLink(),
                        'parents' => $file->getParents(),
                        'isFolder' => false,
                    ], $response->getFiles());
                } catch (\Google\Service\Exception $e) {
                    $body = json_decode($e->getMessage(), true);

                    if (isset($body['error']['code']) && (int) $body['error']['code'] === 401) {
                        throw new \RuntimeException('TOKEN_EXPIRED');
                    }

                    throw $e;
                }
            });
        } catch (\Exception $e) {
            Log::error('Erro ao listar arquivos do Drive (apenas Google Sheets)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    public function getFile(User $user, string $fileId): array
    {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            $file = $service->files->get($fileId, [
                'fields' => 'id,name,mimeType,size,modifiedTime,createdTime,webViewLink,iconLink,parents,permissions,owners,lastModifyingUser,description',
                'supportsAllDrives' => true,
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'modifiedTime' => $file->getModifiedTime(),
                'createdTime' => $file->getCreatedTime(),
                'webViewLink' => $file->getWebViewLink(),
                'iconLink' => $file->getIconLink(),
                'parents' => $file->getParents(),
                'description' => $file->getDescription(),
                'owners' => $file->getOwners(),
                'lastModifyingUser' => $file->getLastModifyingUser(),
                'isFolder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao obter arquivo do Drive', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    public function clearCache(User $user): void
    {
        $pattern = "drive_files_{$user->id}_*";
        Cache::flush();
    }
}