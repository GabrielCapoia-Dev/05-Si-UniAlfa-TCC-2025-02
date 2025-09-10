<?php
namespace App\Livewire;

use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DriveFilePicker extends Component
{
    public string $search = '';
    public array $files = [];
    public array $selectedFiles = [];
    public bool $error = false;
    public string $errorMessage = '';
    public string $viewMode = 'grid';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public array $breadcrumbs = [];
    public ?string $currentFolderId = null;
    public bool $loading = false;

    public function mount()
    {
        $this->breadcrumbs = [['name' => 'Meu Drive', 'id' => null]];
        $this->loadFiles();
    }

    public function updatedSearch()
    {
        $this->loadFiles();
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function sortFiles(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        $this->loadFiles();
    }

    public function selectFile(string $fileId)
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = array_filter($this->selectedFiles, fn($id) => $id !== $fileId);
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }

    public function selectAllFiles()
    {
        if (count($this->selectedFiles) === count($this->files)) {
            $this->selectedFiles = [];
        } else {
            $this->selectedFiles = array_column($this->files, 'id');
        }
    }

    public function openFolder(string $folderId, string $folderName)
    {
        $this->currentFolderId = $folderId;
        $this->breadcrumbs[] = ['name' => $folderName, 'id' => $folderId];
        $this->selectedFiles = [];
        $this->loadFiles();
    }

    public function navigateToBreadcrumb(int $index)
    {
        $this->breadcrumbs = array_slice($this->breadcrumbs, 0, $index + 1);
        $this->currentFolderId = $this->breadcrumbs[$index]['id'] ?? null;
        $this->selectedFiles = [];
        $this->loadFiles();
    }

    public function refreshFiles()
    {
        $this->loadFiles();
    }

    public function downloadFile(string $fileId)
    {
        try {
            $service = app(GoogleDriveService::class);
            $user = Auth::user();
            
            $downloadUrl = $service->getDownloadUrl($user, $fileId);
            $this->dispatch('download-file', url: $downloadUrl);
        } catch (\Throwable $e) {
            $this->dispatch('show-notification', type: 'error', message: 'Erro ao baixar arquivo: ' . $e->getMessage());
        }
    }

    public function shareFile(string $fileId)
    {
        $this->dispatch('open-share-modal', fileId: $fileId);
    }

    private function loadFiles()
    {
        $this->loading = true;
        $this->error = false;
        $this->errorMessage = '';

        try {
            /** @var \App\Models\User */
            $user = Auth::user();
            if (!$user || !method_exists($user, 'hasGoogleOauth') || !$user->hasGoogleOauth()) {
                $this->files = [];
                $this->error = true;
                $this->errorMessage = 'Sua conta Google não está conectada.';
                $this->loading = false;
                return;
            }

            $service = app(GoogleDriveService::class);
            
            // Usar método mais simples se o serviço original não suportar os novos parâmetros
            if (method_exists($service, 'listFiles')) {
                try {
                    $this->files = $service->listFiles(
                        $user,
                        $this->search,
                        50,
                        $this->currentFolderId ?? null,
                        $this->sortBy . ' ' . $this->sortDirection
                    );
                } catch (\ArgumentCountError $e) {
                    // Fallback para método original
                    $this->files = $service->listFiles($user, $this->search, 50);
                }
            } else {
                $this->files = [];
                $this->error = true;
                $this->errorMessage = 'Serviço do Google Drive não disponível.';
            }

        } catch (\Throwable $e) {
            $this->files = [];
            $this->error = true;
            $this->errorMessage = 'Falha ao carregar seus arquivos do Drive. ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function formatFileSize(int $size): string
    {
        if ($size === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $fileSize = (float) $size;
        
        while ($fileSize >= 1024 && $unitIndex < count($units) - 1) {
            $fileSize /= 1024;
            $unitIndex++;
        }
        
        return round($fileSize, 1) . ' ' . $units[$unitIndex];
    }

    public function getFileTypeFromMimeType(string $mimeType): string
    {
        $typeMap = [
            'application/vnd.google-apps.folder' => 'folder',
            'application/vnd.google-apps.document' => 'document',
            'application/vnd.google-apps.spreadsheet' => 'spreadsheet',
            'application/vnd.google-apps.presentation' => 'presentation',
            'application/pdf' => 'pdf',
        ];

        foreach ($typeMap as $mime => $type) {
            if (str_contains($mimeType, $mime)) {
                return $type;
            }
        }

        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        if (str_starts_with($mimeType, 'audio/')) return 'audio';

        return 'file';
    }

    public function render()
    {
        return view('livewire.drive-file-picker');
    }
}