<?php

namespace App\Livewire;

use App\Services\GoogleDriveService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
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
    public string $modelClass;
    public ?string $err = null;

    public function mount(string $modelClass)
    {
        $this->modelClass = $modelClass;
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
        $this->selectedFiles = in_array($fileId, $this->selectedFiles)
            ? []
            : [$fileId];
    }

    public function openFolder(string $folderId, string $folderName)
    {
        $this->currentFolderId = $folderId;
        $this->breadcrumbs[] = ['name' => $folderName, 'id' => $folderId];
        $this->selectedFiles = [];
        $this->loadFiles();
    }

    public function confirmSelection(GoogleDriveService $drive)
    {
        if (empty($this->selectedFiles)) {
            return Notification::make()
                ->title('Nenhum arquivo selecionado')
                ->body('Selecione uma planilha antes de continuar.')
                ->danger()
                ->persistent()
                ->send();
        }

        $user = Auth::user();
        $fileId = $this->selectedFiles[0];

        try {
            $file = $drive->getFile($user, $fileId);

            if ($file['mimeType'] !== 'application/vnd.google-apps.spreadsheet') {
                return Notification::make()
                    ->title('Arquivo inválido')
                    ->body('Você deve selecionar uma planilha do Google Sheets.')
                    ->danger()
                    ->persistent()
                    ->send();
            }

            $model = app($this->modelClass);

            if (!method_exists($model, 'importGoogleSheet')) {
                throw new \RuntimeException("A model {$this->modelClass} não implementa importGoogleSheet().");
            }

            $result = $model->importGoogleSheet($fileId);

            Notification::make()
                ->title('Importação concluída')
                ->body("Planilha {$file['name']} processada com sucesso")
                ->success()
                ->duration(8000)
                ->send();

            $this->dispatch('closeModal', id: $fileId);
        } catch (\RuntimeException $e) {
            $lines = preg_split("/\r?\n/", $e->getMessage());

            // filtra cabeçalho e linhas vazias
            $filtered = array_values(array_filter($lines, function ($l) {
                $t = trim($l);
                if ($t === '') return false;
                if (stripos($t, 'Importação cancelada') !== false) return false;
                if (stripos($t, 'Resumo por coluna') !== false) return false;
                return true;
            }));

            $shown = array_slice($filtered, 0, 20);

            // Renderiza como lista HTML
            $lis  = implode('', array_map(fn($l) => '<li>' . e(trim($l)) . '</li>', $shown));
            $html = new HtmlString('<ul class="list-disc ps-5 space-y-1">' . $lis . '</ul>');

            Notification::make()
                ->title('Importação cancelada')
                ->body($html)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao processar planilha')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function loadFiles()
    {
        $this->loading = true;
        $this->error = false;
        $this->errorMessage = '';

        try {
            /** @var \App\Models\User $user */
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
                $this->error = true;
                $this->errorToken = true;
            } else {
                $this->error = true;
                $this->errorMessage = 'Falha ao carregar seus arquivos do Drive.';
            }

            $this->files = [];
        } catch (\Throwable $e) {
            $this->error = true;
            $this->errorMessage = 'Falha ao carregar seus arquivos do Drive. ' . $e->getMessage();
            $this->files = [];
        } finally {
            $this->loading = false;
        }
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
