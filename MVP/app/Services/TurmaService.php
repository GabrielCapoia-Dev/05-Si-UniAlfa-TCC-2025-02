<?php

namespace App\Services;
use Filament\Tables\Table;
use Filament\Tables;
use App\Models\User;
use App\Models\Escola;
use App\Models\Serie;
use Illuminate\Support\Facades\Auth;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\TurmaResource\Pages\ManageTurmas;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;
use Filament\Notifications\Notification;


class TurmaService
{

    //** Configurar Tabela */
    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela($user))
            ->actions($this->acoesTabela($user))
            ->bulkActions($this->acoesEmMassa($user))
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    //** Colunas da Tabela */
    private function colunasTabela(): array
    {
        return [
            Tables\Columns\TextColumn::make('escola.nome')
                ->label('Escola')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('serie.nome')
                ->label('Série')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('turma')
                ->label('Turma')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('turno')
                ->label('Turno')
                ->sortable(),

            Tables\Columns\TextColumn::make('alunos_count')
                ->label('Qtd. Alunos')
                ->counts('alunos')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Criado em')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Atualizado em')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    //** Filtros da Tabela */
    private function filtrosTabela(): array
    {
        return [
            Tables\Filters\SelectFilter::make('id_escola')
                ->label('Escola')
                ->relationship('escola', 'nome'),

            Tables\Filters\SelectFilter::make('id_serie')
                ->label('Série')
                ->relationship('serie', 'nome'),

            Tables\Filters\SelectFilter::make('turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                    'Integral' => 'Integral',
                ]),
        ];
    }

    //** Ações da Tabela */
    private function acoesTabela(User $user): array
    {
        return [
            Tables\Actions\Action::make('viewAlunos')
                ->label('Ver Alunos')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn() => $this->podeListarAlunos($user))
                ->url(fn($record) => route('filament.admin.resources.alunos.index', [
                    'tableFilters' => [
                        'id_turma' => [
                            'value' => $record->id,
                        ],
                    ],
                ])),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->before(function ($record, $action) {
                    if ($record->alunos()->exists()) {
                        Notification::make()
                            ->title('Não é possível excluir esta turma.')
                            ->body('Existem alunos vinculados a ela.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    //** Ações em Massa da Tabela */
    private function acoesEmMassa(?User $user): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make()
                ->before(function ($records, $action) {

                    foreach ($records as $record) {
                        if ($record->alunos()->exists()) {
                            Notification::make()
                                ->title('Ação cancelada.')
                                ->body('Não é possivel excluir turmas com alunos vinculados.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }
                }),
        ];
    }

    //** Verifica se tem permissao de listar alunos */
    private function podeListarAlunos(User $user): bool
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user?->hasPermissionTo('Listar Alunos');
    }

    //** Verifica se tem permissao de criar turma */
    public function configurarFormulario(Form $form, ?User $user): Form
    {
        return $form->schema($this->schemaFormulario($user));
    }

    //** Schema do FOrm */
    private function schemaFormulario($user): array
    {
        return [
            Forms\Components\Select::make('id_escola')
                ->label('Escola')
                ->relationship('escola', 'nome')
                ->required()
                ->preload()
                ->searchable()
                ->default(fn() => Auth::user()?->id_escola)
                ->dehydrated(true)
                ->disabled(fn() => app(UserService::class)->ehAdmin(Auth::user()) ? false : true),

            Forms\Components\Select::make('id_serie')
                ->label('Série')
                ->relationship('serie', 'nome')
                ->required()
                ->preload()
                ->searchable(),

            Forms\Components\TextInput::make('turma')
                ->label('Turma')
                ->required()
                ->maxLength(1)
                ->live(onBlur: false)
                ->afterStateUpdated(function ($state, callable $set) {
                    $filtrado = strtoupper(preg_replace('/[^A-Za-z]/', '', $state ?? ''));
                    $set('turma', $filtrado);
                })
                ->dehydrateStateUsing(fn($state) => strtoupper($state ?? ''))
                ->rule(
                    fn($get, $record) =>
                    "unique:turmas,turma," . ($record?->id ?? 'NULL') . ",id,id_escola,{$get('id_escola')},id_serie,{$get('id_serie')},turno,{$get('turno')}"
                )
                ->validationMessages([
                    'unique' => 'Ja existe essa turma na escola selecionada.',
                ])
                ->placeholder('Ex.: A')
                ->helperText('Digite apenas uma letra (A–Z).'),

            Forms\Components\Select::make('turno')
                ->label('Turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                    'Integral' => 'Integral',
                ])
                ->required(),
        ];
    }

    public function queryTabela(): Builder
    {
        $query = app(ManageTurmas::class)->getResource()::getEloquentQuery()
            ->with(['escola', 'serie']); 

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && (!$user->hasRole('Admin')) && !empty($user->id_escola)) {
            $query->where($query->getModel()->getTable() . '.id_escola', $user->id_escola);
        }

        return $query;
    }

    public function forcarVinculoComEscola(array $data): array
    {

        $admin = app(UserService::class)->ehAdmin(Auth::user());

        if ($admin && !empty($auth->id_escola)) {
            $data['id_escola'] = $auth->id_escola;
        }

        return $data;
    }

    public function acoesDoCabecalhoDaPagina(): array
    {
        $hasEscolas = Escola::exists();
        $hasSeries = Serie::exists();

        if ($hasEscolas && $hasSeries) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        $actions = [];

        if (! $hasEscolas) {
            $actions[] = Actions\Action::make('Cadastrar Escolas')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-academic-cap')
                ->url(route('filament.admin.resources.escolas.index'));
        }

        if (! $hasSeries) {
            $actions[] = Actions\Action::make('Cadastrar Séries')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-collection')
                ->url(route('filament.admin.resources.series.index'));
        }

        return $actions;
    }
}