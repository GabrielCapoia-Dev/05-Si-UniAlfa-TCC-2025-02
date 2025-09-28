<?php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Turma;

class CreateAluno extends CreateRecord
{
    protected static string $resource = AlunoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User|null $auth */
        $auth = Auth::user();

        // Para não-admin, garanta que a turma selecionada pertence à escola do criador.
        if ($auth && !$auth->hasRole('Admin') && !empty($auth->id_escola)) {
            $isFromSameSchool = Turma::query()
                ->where('id', $data['id_turma'] ?? null)
                ->where('id_escola', $auth->id_escola)
                ->exists();

            if (! $isFromSameSchool) {
                throw ValidationException::withMessages([
                    'id_turma' => 'A turma selecionada não pertence à sua escola.',
                ]);
            }
        }

        return $data;
    }
}
