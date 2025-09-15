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
    public bool $errorToken = false;
    public string $errorMessage = "";
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
        if (!empty($this->search)) {
            $this->currentFolderId = null;
        }

        $this->selectedFiles = [];
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

    public function goBack()
    {
        if (count($this->breadcrumbs) > 1) {
            array_pop($this->breadcrumbs); // remove a última pasta
            $this->currentFolderId = end($this->breadcrumbs)['id'] ?? null;
            $this->selectedFiles = [];
            $this->loadFiles();
        }
    }


    public function selectFile(string $fileId)
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = [];
        } else {
            $this->selectedFiles = [$fileId];
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

    public function confirmSelection()
    {
        if (empty($this->selectedFiles)) {
            $this->dispatch('show-notification', [
                'type' => 'warning',
                'message' => 'Selecione pelo menos um arquivo.'
            ]);
            return;
        }

        // Get selected files data
        $selectedFilesData = array_filter($this->files, function ($file) {
            return in_array($file['id'], $this->selectedFiles);
        });

        // Dispatch event to parent component with selected files
        $this->dispatch('files-selected', [
            'files' => array_values($selectedFilesData),
            'count' => count($selectedFilesData)
        ]);

        // Close modal
        $this->dispatch('close-modal');

        // Show success message
        $this->dispatch('show-notification', [
            'type' => 'success',
            'message' => count($selectedFilesData) . ' arquivo(s) selecionado(s) com sucesso!'
        ]);
    }

    public function cancelSelection()
    {
        $this->selectedFiles = [];
        $this->dispatch('close-modal');
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
                return;
            }

            $service = app(GoogleDriveService::class);
            $orderBy = $this->sortBy . ' ' . $this->sortDirection;

            $this->files = $service->listFiles(
                $user,
                $this->search,
                50,
                $this->currentFolderId ?? null,
                $orderBy
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'TOKEN_EXPIRED') {
                $this->files = [];
                $this->error = true;
                $this->errorToken = true;
            } else {
                $this->files = [];
                $this->error = true;
                $this->errorMessage = 'Falha ao carregar seus arquivos do Drive.';
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
            'application/vnd.google-apps.spreadsheet' => 'planilha',
            'application/vnd.ms-excel' => 'excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
            'text/csv' => 'csv',
        ];

        foreach ($typeMap as $mime => $type) {
            if (str_contains($mimeType, $mime)) {
                return $type;
            }
        }

        return 'arquivo';
    }

    public function render()
    {
        return view('livewire.drive-file-picker');
    }
}