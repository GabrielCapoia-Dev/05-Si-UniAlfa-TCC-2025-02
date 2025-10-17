<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;

class RoleService
{

    public function adminRole($record): bool
    {
        $roles = [
            'Admin',
            'Secretario',
            'Usuario'
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }
        return false;
    }

    public function bloquearCampo($record, $context): bool
    {

        if ($context == 'create') {
            return false;
        }

        $roles = [
            'Admin',
            'Secretario',
            'Usuario'
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }

        return false;
    }
    public function bloquearExclusao($record): bool
    {

        $roles = [
            'Admin',
            'Secretario',
            'Usuario'
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }

        return false;
    }

    public function bloquearSelecaoBulkActions($record): bool
    {
        $bloqueados = ['Admin', 'Secretario', 'Usuario'];
        return !in_array($record->name, $bloqueados);
    }

    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela())
            ->actions($this->acoesTabela())
            ->bulkActions($this->acoesEmMassa())
            ->checkIfRecordIsSelectableUsing(fn($record) => $this->bloquearSelecaoBulkActions($record));
    }

    public function configurarFormulario(Form $form): Form
    {
        return $form
            ->schema($this->camposFormulario());
    }

    private function camposFormulario(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nivel de acesso')
                ->required()
                ->minLength(3)
                ->maxLength(100)
                ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                ->validationMessages([
                    'regex' => 'Use apenas letras, sem caracteres especiais.',
                    'unique' => 'Já existe um Nivel de acesso com este nome.',
                ])
                ->disabled(
                    fn($record, $context) =>
                    $this->bloquearCampo($record, $context)
                )
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('permissions')
                ->label('Permissão de execução')
                ->multiple()
                ->relationship('permissions', 'name')
                ->preload()
                ->required()
        ];
    }

    private function colunasTabela(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nivel de acesso')
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Criado em')
                ->since()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Atualizado em')
                ->since()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    private function filtrosTabela(): array
    {
        return [];
    }

    private function acoesTabela(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->disabled(fn($record) => app(RoleService::class)->adminRole($record)),

            Tables\Actions\DeleteAction::make()
                ->disabled(fn($record) => app(RoleService::class)->bloquearExclusao($record)),
        ];
    }

    private function acoesEmMassa(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make()
                ->visible(function () {
                    $user = Auth::user();
                    return app(UserService::class)->ehAdmin($user);
                })
        ];
    }
}
