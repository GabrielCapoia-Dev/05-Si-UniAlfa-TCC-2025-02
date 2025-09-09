<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DominioEmailResource\Pages;
use App\Filament\Resources\DominioEmailResource\RelationManagers;
use App\Models\DominioEmail;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DominioEmailResource extends Resource
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
        return 'Quantidade de dominios permitidos';
    }

    protected static ?string $model = DominioEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = "Gerenciamento";

    public static ?string $label = 'Dominio Permitido';

    public static ?string $pluralLabel = 'Dominios Permitidos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('dominio_email')
                    ->label('Dominio Permitido')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/')
                    ->helperText('Digite o dominio sem o @, exemplo: dominio.com.br')
                    ->placeholder('dominio.com.br'),

                TextInput::make('setor')
                    ->label('Setor')
                    ->required(),

                Toggle::make('status')
                    ->label('Status')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dominio_email')
                    ->label('Email Dominio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('setor')
                    ->label('Setor')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label('Status')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDominioEmails::route('/'),
        ];
    }
}