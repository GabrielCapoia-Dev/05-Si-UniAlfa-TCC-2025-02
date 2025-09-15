<?php

namespace App\Livewire;

use App\Services\GoogleDriveService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Filament\Notifications\Actions\Action;


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
    public string $modelClass;
    public ?string $err = null;

    public function mount(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->breadcrumbs = [['name' => 'Meu Drive', 'id' => null]];
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

    public function refreshFiles()
    {
        $this->loadFiles();
    }

    private function validateSheetForModel(string $fileId): void
    {
        if (! class_exists($this->modelClass)) {
            throw new \RuntimeException("Model {$this->modelClass} não encontrada.");
        }

        $model = app($this->modelClass);

        if (! method_exists($model, 'validateGoogleSheet')) {
            throw new \RuntimeException("A model {$this->modelClass} não implementa validateGoogleSheet().");
        }

        $model->validateGoogleSheet($fileId);
    }

    public function confirmSelection(\App\Services\GoogleDriveService $drive)
    {
        if (empty($this->selectedFiles)) {
            return \Filament\Notifications\Notification::make()
                ->title('Nenhum arquivo selecionado')
                ->body('Selecione uma planilha antes de continuar.')
                ->danger()->persistent()->send();
        }

        $user   = \Illuminate\Support\Facades\Auth::user();
        $fileId = $this->selectedFiles[0];

        try {
            $file = $drive->getFile($user, $fileId);

            if ($file['mimeType'] !== 'application/vnd.google-apps.spreadsheet') {
                return \Filament\Notifications\Notification::make()
                    ->title('Arquivo inválido')
                    ->body('Você deve selecionar uma planilha do Google Sheets.')
                    ->danger()->persistent()->send();
            }

            // ===== IMPORTAR de acordo com a model =====
            if (! class_exists($this->modelClass)) {
                throw new \RuntimeException("Model {$this->modelClass} não encontrada.");
            }
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = app($this->modelClass);

            if (! method_exists($model, 'importGoogleSheet')) {
                throw new \RuntimeException("A model {$this->modelClass} não implementa importGoogleSheet().");
            }

            $result = $model->importGoogleSheet($fileId);

            \Filament\Notifications\Notification::make()
                ->title('Importação concluída')
                ->body("Planilha **{$file['name']}** processada com sucesso.\n" .
                    "Registros inseridos/atualizados: **{$result['imported_or_updated']}**.")
                ->success()
                ->duration(8000)
                ->send();

            $this->dispatch('closeModal', id: $fileId);
        } catch (\RuntimeException $e) {
            // Erros de validação de linhas: mostra como popup grande
            $msg = $e->getMessage();

            // para não estourar a UI, corta texto após 20 linhas
            $lines = explode("\n", $msg);
            $shown = array_slice($lines, 0, 20);
            if (count($lines) > 20) {
                $shown[] = '... (erros adicionais omitidos)';
            }

            \Filament\Notifications\Notification::make()
                ->title('Importação cancelada')
                ->body(implode("\n", $shown))
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            \Filament\Notifications\Notification::make()
                ->title('Erro ao processar planilha')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
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