<?php

namespace App\Services;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Models\Escola;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;


class EscolaService
{
    /** Opções de escolas conforme perfil: Admin vê todas; secretário só a sua. */
    public function opcoesDeEscolasParaUsuario(?User $user): array
    {
        if (app(UserService::class)->ehAdmin($user) || empty($user?->id_escola)) {
            return $this->opcoesDeEscolas();
        }

        return Escola::query()
            ->whereKey($user->id_escola)
            ->pluck('nome', 'id')
            ->toArray();
    }

    /** Opções de escolas ordenadas. */
    public function opcoesDeEscolas(): array
    {
        return Escola::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    /** Configura a tabela completa (paginações, colunas, filtros, ações, ordenação). */
    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela($user))
            ->actions($this->acoesTabela())
            ->bulkActions($this->acoesEmMassa($user))
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    /** Colunas da listagem de escolas (espelha sua Resource). */
    private function colunasTabela(): array
    {
        return [
            Tables\Columns\TextColumn::make('tipo')
                ->label('Tipo')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('nome')
                ->label('Nome')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('logradouro')
                ->label('Logradouro')
                ->wrap()
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('bairro')
                ->label('Bairro')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('cidade')
                ->label('Cidade')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('estado')
                ->label('UF')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('cep')
                ->label('CEP')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('numero')
                ->label('Número')
                ->toggleable()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('complemento')
                ->label('Complemento')
                ->wrap()
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Criado em')
                ->dateTime("d/m/Y H:i")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Atualizado em')
                ->dateTime("d/m/Y H:i")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** Filtros (com default municipal, como na sua Resource). */
    private function filtrosTabela(?User $user): array
    {
        return [
            SelectFilter::make('tipo')
                ->label('Tipo')
                ->options([
                    'municipal' => 'Municipal',
                    'estadual'  => 'Estadual',
                ]),
        ];
    }

    /** Ações por linha (inclui o "Ver Turmas"). */
    private function acoesTabela(): array
    {
        return [
            Tables\Actions\Action::make('viewTurmas')
                ->label('Ver Turmas')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn($record) => route('filament.admin.resources.turmas.index', [
                    'tableFilters' => [
                        'id_escola' => [
                            'value' => $record->id,
                        ],
                    ],
                ])),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->action(fn($record) => $this->deletarEscola($record)),
        ];
    }

    /** Ações em massa (mantém Delete e um export em massa opcional). */
    private function acoesEmMassa(?User $user): array
    {
        return [
            FilamentExportBulkAction::make('exportar_filtrados')
                ->label('Exportar XLSX')
                ->defaultFormat('xlsx')
                ->directDownload(),
        ];
    }

    public function deletarEscola($record): bool
    {
        $relacionamentos = [
            'turmas',
            'users',
            'rotas',
        ];

        foreach ($relacionamentos as $rel) {
            if (method_exists($record, $rel) && $record->$rel()->exists()) {

                if ($rel == "users") {
                    $rel = "usuários";
                }

                Notification::make()
                    ->title('Operação cancelada')
                    ->body("Não foi possível excluir esta escola pois esta vinculada a {$rel}.")
                    ->danger()
                    ->send();

                return false;
            }
        }

        $record->delete();

        Notification::make()
            ->title('Escola excluída com sucesso')
            ->success()
            ->send();

        return true;
    }
}
