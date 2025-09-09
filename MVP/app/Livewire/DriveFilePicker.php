<?php
namespace App\Livewire;

use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DriveFilePicker extends Component
{
    public string $search = '';
    public array $files = [];
    public bool $error = false;
    public string $errorMessage = '';

    public function mount(GoogleDriveService $service): void
    {
        $this->loadFiles($service);
    }

    public function updatedSearch(GoogleDriveService $service): void
    {
        $this->loadFiles($service);
    }

    private function loadFiles(GoogleDriveService $service): void
    {
        $this->error = false;
        $this->errorMessage = '';

        /** @var \App\Models\User */
        $user = Auth::user();
        if (! $user || ! $user->hasGoogleOauth()) {
            $this->files = [];
            $this->error = true;
            $this->errorMessage = 'Sua conta Google não está conectada.';
            return;
        }

        try {
            $this->files = $service->listFiles($user, $this->search, 50);
        } catch (\Throwable $e) {
            $this->files = [];
            $this->error = true;
            $this->errorMessage = 'Falha ao carregar seus arquivos do Drive. ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.drive-file-picker');
    }
}
