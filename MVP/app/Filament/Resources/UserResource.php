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
use App\Models\IgnoredUser;

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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole('Admin')) {
            return null;
        }

        $ignoredUserIds = IgnoredUser::where('admin_id', $user->id)
            ->pluck('user_id')
            ->toArray();

        $count = static::getModel()::where('email_approved', false)
            ->whereNotIn('id', $ignoredUserIds)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Novos Usuários';
    }


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
                    ->required()
                    ->disabled(function ($record) {
                        return $record && $record->hasRole('Admin');
                    }),


                Forms\Components\Toggle::make('email_approved')
                    ->label('Verificação de acesso')
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark')
                    ->default(true)
                    ->disabled(function ($record, $context) {
                        if ($context === 'create') {
                            return false;
                        }

                        if ($record->hasRole('Admin')) {
                            return true;
                        }

                        return false;
                    }),

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
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        if (!$user || $user->hasRole('Admin')) {
                            return true;
                        }
                        return false;
                    }),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_escola')
                    ->label('Escola')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->escola ? $record->escola->nome : '-';
                    })
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
                    ->disabled(function ($record) {
                        $user = Auth::user();

                        // Desativa o toggle se for o próprio usuário
                        return $user && $record && $user->id === $record->id;
                    })
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        if (!$user) {
                            return false;
                        }

                        return $user->hasRole('Admin');
                    })
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
                    ->searchable()
                    ->sortable()->getStateUsing(fn(User $record) => $record->roles->first()?->name ?? '-')
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($records, $action) {
                        if ($records->contains(fn($user) => $user->hasRole('Admin'))) {
                            $action->halt();
                        }
                    })
                    ->disabled(function ($record) {
                        if ($record->hasRole('Admin')) {
                            return true;
                        }
                    })
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        if (!$user) {
                            return false;
                        }
                        return $user->hasRole('Admin');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, $action) {
                            if ($records->contains(fn($user) => $user->hasRole('Admin'))) {
                                $action->halt();
                            }
                        })
                        ->visible(function () {
                            /** @var \App\Models\User|null $user */
                            $user = Auth::user();
                            if (!$user) {
                                return false;
                            }
                            return $user->hasRole('Admin');
                        }),
                ])
            ]);
    }

    protected function getTableQuery()
    {
        $admin = Auth::user();

        $pendingUsers = static::getModel()::where('email_approved', false)->pluck('id');

        foreach ($pendingUsers as $userId) {
            IgnoredUser::firstOrCreate([
                'admin_id' => $admin->id,
                'user_id'  => $userId,
            ]);
        }

        return parent::getTableQuery();
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
            $query->whereHas('roles', fn($q) => $q->where('name', '!=', 'Admin'));
        }

        return $query;
    }
}
