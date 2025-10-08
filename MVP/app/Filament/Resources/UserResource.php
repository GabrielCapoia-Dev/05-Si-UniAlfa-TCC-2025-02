<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\UserService as Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    public static ?string $modelLabel = 'Usuário';
    protected static ?string $navigationGroup = "Acesso";
    public static ?string $pluralModelLabel = 'Usuários';
    public static ?string $slug = 'usuarios';

    public static function getNavigationBadge(): ?string
    {
        return app(Service::class)->badgeNavegacaoParaNovosUsuarios(Auth::user());
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Novos Usuários';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nome de usuário')
                ->required(),

            Forms\Components\TextInput::make('email')
                ->label('E-mail')
                ->unique(ignoreRecord: true)
                ->email()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $context): bool => $context === 'create'),

            Forms\Components\Select::make('role')
                ->label('Nivel de acesso')
                ->relationship('roles', 'name', function (Builder $query) {
                    return app(Service::class)->opcoesDeRoles($query, Auth::user());
                })
                ->preload()
                ->required()
                ->disabled(fn ($context, $record) =>
                    app(Service::class)->desabilitarCampoRole(Auth::user(), $record, $context)
                ),

            Forms\Components\Toggle::make('email_approved')
                ->label('Verificação de acesso')
                ->inline(false)
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-s-check')
                ->offIcon('heroicon-s-x-mark')
                ->default(true)
                ->visible(fn ($record, $context) =>
                    app(Service::class)->podeVerToggleAprovacaoEmail(Auth::user(), $record, $context)
                ),

            Forms\Components\Section::make('Vínculo com Escola')
                ->icon('heroicon-o-identification')
                ->description('Aqui mostra se o usuário esta vinculado a uma escola.')
                ->schema([
                    Forms\Components\Select::make('id_escola')
                        ->label('Escola')
                        ->relationship('escola', 'nome')
                        ->preload()
                        ->searchable(),
                ])
                ->visible(fn ($record, $context) =>
                    app(Service::class)->podeVerSecaoEscola(Auth::user(), $record, $context)
                ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->checkIfRecordIsSelectableUsing(fn(User $record) =>
                app(Service::class)->podeSelecionarRegistro(Auth::user(), $record)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id_escola')
                    ->label('Escola')
                    ->searchable()
                    ->formatStateUsing(fn($state, $record) => $record->escola?->nome ?? '-')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome de usuário')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('email_approved')
                    ->label('Verificação de Acesso')
                    ->sortable()
                    ->disabled(fn ($record) =>
                        app(Service::class)->desabilitarToggleAprovacaoEmail(Auth::user(), $record)
                    )
                    ->visible(fn () =>
                        app(Service::class)->podeVerToggleAprovacaoEmail(Auth::user(), null, 'table')
                    )
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark')
                    ->columnSpan(1),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verificado em')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->email_approved) {
                            return '--/--/-- --:--:--';
                        }
                        return $state ? $state->format('d/m/Y H:i:s') : '-';
                    }),

                Tables\Columns\TextColumn::make('role')
                    ->label('Nivel de acesso')
                    ->sortable()
                    ->getStateUsing(fn(User $record) => $record->roles->first()?->name ?? '-')
                    ->toggleable(isToggledHiddenByDefault: false),

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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        if (! app(Service::class)->podeDeletar(Auth::user(), $record)) {
                            $action->failure();
                            $action->halt();
                        }
                    })
                    ->disabled(fn (User $record) =>
                        // mantém mesmas regras visuais de disabled
                        ($record->id === 1) || (Auth::id() === $record->id)
                    )
                    ->visible(fn () =>
                        app(Service::class)->ehAdmin(Auth::user())
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, $action) {
                            // $records é uma Collection
                            if (! app(Service::class)->podeDeletarEmLote(Auth::user(), $records)) {
                                $action->halt();
                            }
                        })
                        ->visible(fn () =>
                            app(Service::class)->ehAdmin(Auth::user())
                        ),
                ])
            ]);
    }

    protected function getTableQuery()
    {
        if ($admin = Auth::user()) {
            app(Service::class)->sincronizarIgnoradosParaAdmin($admin);
        }
        return parent::getTableQuery();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return app(Service::class)->listarUsuariosQuery(
            parent::getEloquentQuery(),
            Auth::user()
        );
    }
}
