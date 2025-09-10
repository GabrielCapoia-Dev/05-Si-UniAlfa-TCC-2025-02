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

    public function listFiles(
        User $user,
        string $search = '',
        int $limit = 50,
        ?string $folderId = null,
        string $orderBy = 'name asc'
    ): array {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            // Construir query
            $query = [];

            // Filtrar por pasta pai
            if ($folderId) {
                $query[] = "'{$folderId}' in parents";
            } else {
                $query[] = "'root' in parents";
            }

            // Excluir lixeira
            $query[] = "trashed = false";

            // Filtro de busca
            if (!empty($search)) {
                $query[] = "name contains '{$search}'";
            }
            // Filtrar apenas planilhas
            $spreadsheetMimeTypes = [
                "application/vnd.google-apps.spreadsheet",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "text/csv"
            ];

            $mimeTypeFilter = '(' . implode(' or ', array_map(fn($m) => "mimeType='{$m}'", $spreadsheetMimeTypes)) . ')';
            $query[] = $mimeTypeFilter;

            $queryString = implode(' and ', $query);

            // Fazer a requisição com cache
            $cacheKey = "drive_files_" . $user->id . "_" . md5($queryString . $orderBy . $limit);

            return Cache::remember($cacheKey, 300, function () use ($service, $queryString, $orderBy, $limit) {
                $response = $service->files->listFiles([
                    'q' => $queryString,
                    'orderBy' => $orderBy,
                    'pageSize' => $limit,
                    'fields' => 'files(id,name,mimeType,size,modifiedTime,webViewLink,iconLink,parents,permissions)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ]);

                return array_map(function ($file) {
                    return [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'modifiedTime' => $file->getModifiedTime(),
                        'webViewLink' => $file->getWebViewLink(),
                        'iconLink' => $file->getIconLink(),
                        'parents' => $file->getParents(),
                        'isFolder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
                        'canEdit' => $this->canEditFile($file),
                        'canShare' => $this->canShareFile($file),
                    ];
                }, $response->getFiles());
            });
        } catch (\Exception $e) {
            Log::error('Erro ao listar arquivos do Drive', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'canEdit' => $this->canEditFile($file),
                'canShare' => $this->canShareFile($file),
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

    public function getDownloadUrl(User $user, string $fileId): string
    {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            $file = $service->files->get($fileId, [
                'fields' => 'mimeType,webContentLink',
                'supportsAllDrives' => true,
            ]);

            // Para arquivos do Google Workspace, usar export
            if (str_starts_with($file->getMimeType(), 'application/vnd.google-apps.')) {
                return $this->getExportUrl($service, $fileId, $file->getMimeType());
            }

            // Para arquivos regulares, usar webContentLink
            return $file->getWebContentLink() ?: throw new \Exception('Arquivo não pode ser baixado');
        } catch (\Exception $e) {
            Log::error('Erro ao obter URL de download', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function shareFile(User $user, string $fileId, array $permissions = []): array
    {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            $results = [];

            foreach ($permissions as $permission) {
                $drivePermission = new \Google\Service\Drive\Permission();
                $drivePermission->setType($permission['type']); // 'user', 'group', 'domain', 'anyone'
                $drivePermission->setRole($permission['role']); // 'reader', 'writer', 'commenter'

                if (isset($permission['emailAddress'])) {
                    $drivePermission->setEmailAddress($permission['emailAddress']);
                }

                $result = $service->permissions->create($fileId, $drivePermission, [
                    'sendNotificationEmail' => $permission['sendEmail'] ?? false,
                    'supportsAllDrives' => true,
                ]);

                $results[] = [
                    'id' => $result->getId(),
                    'type' => $result->getType(),
                    'role' => $result->getRole(),
                    'emailAddress' => $result->getEmailAddress(),
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Erro ao compartilhar arquivo', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getFolderPath(User $user, string $folderId): array
    {
        try {
            $client = $this->getClientFor($user);
            $service = new Drive($client);

            $path = [];
            $currentId = $folderId;

            while ($currentId && $currentId !== 'root') {
                $folder = $service->files->get($currentId, [
                    'fields' => 'id,name,parents',
                    'supportsAllDrives' => true,
                ]);

                array_unshift($path, [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                ]);

                $parents = $folder->getParents();
                $currentId = $parents ? $parents[0] : null;
            }

            // Adicionar raiz
            array_unshift($path, [
                'id' => 'root',
                'name' => 'Meu Drive',
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Erro ao obter caminho da pasta', [
                'user_id' => $user->id,
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return [['id' => 'root', 'name' => 'Meu Drive']];
        }
    }

    private function getExportUrl(Drive $service, string $fileId, string $mimeType): string
    {
        $exportMimeTypes = [
            'application/vnd.google-apps.document' => 'application/pdf',
            'application/vnd.google-apps.spreadsheet' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.google-apps.presentation' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        $exportMimeType = $exportMimeTypes[$mimeType] ?? 'application/pdf';

        // Gerar URL de export
        return "https://www.googleapis.com/drive/v3/files/{$fileId}/export?mimeType=" . urlencode($exportMimeType);
    }

    private function canEditFile($file): bool
    {
        $permissions = $file->getPermissions();
        if (!$permissions) return false;

        foreach ($permissions as $permission) {
            if ($permission->getRole() === 'owner' || $permission->getRole() === 'writer') {
                return true;
            }
        }

        return false;
    }

    private function canShareFile($file): bool
    {
        $permissions = $file->getPermissions();
        if (!$permissions) return false;

        foreach ($permissions as $permission) {
            if ($permission->getRole() === 'owner') {
                return true;
            }
        }

        return false;
    }

    public function clearCache(User $user): void
    {
        $pattern = "drive_files_{$user->id}_*";
        Cache::flush(); // Ou implementar uma limpeza mais específica
    }
}
