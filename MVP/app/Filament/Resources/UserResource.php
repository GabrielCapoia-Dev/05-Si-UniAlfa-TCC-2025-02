<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
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

    public static function getNavigationBadge(): ?string
    {
        $value = (string) static::getModel()::count();

        if ($value > 0) {
            return $value;
        }
        return null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Quantidade de usuários cadastrados';
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static ?string $modelLabel = 'Usuário';

    protected static ?string $navigationGroup = "Gerenciamento";

    public static ?string $pluralModelLabel = 'Usuários';

    public static ?string $slug = 'usuarios';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        if (!$user || $user->hasRole('Admin')) {
                            return $query;
                        }
                        return $query->where('name', '!=', 'Admin');
                    })
                    ->preload()
                    ->required(),

                Forms\Components\Toggle::make('email_approved')
                    ->label('Verificação de acesso')
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark')
                    ->required()
                    ->default(true),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome de usuário')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

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

                Tables\Columns\ToggleColumn::make('email_approved')
                    ->label('Verificação de Acesso')
                    ->sortable()
                    ->disabled(function ($record) {
                        $user = Auth::user();

                        // Desativa o toggle se for o próprio usuário
                        return $user && $record && $user->id === $record->id;
                    })
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        // Se não estiver autenticado, esconde
                        if (!$user) {
                            return false;
                        }

                        // Mostra só para Admin
                        return $user->hasRole('Admin');
                    })
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark')
                    ->columnSpan(1),

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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function () {
                            /** @var \App\Models\User|null $user */
                            $user = Auth::user();

                            // Se não estiver autenticado, esconde
                            if (!$user) {
                                return false;
                            }

                            // Mostra só para Admin
                            return $user->hasRole('Admin');
                        }),
                ])
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && !$user->hasRole('Admin')) {
            // Apenas não-admins veem só usuários não-admins
            $query->whereHas('roles', fn($q) => $q->where('name', '!=', 'Admin'));
        }

        return $query;
    }
}