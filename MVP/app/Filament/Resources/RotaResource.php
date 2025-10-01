<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RotaResource\Pages;
use App\Forms\Components\Mapa;
use App\Models\Rota;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RotaResource extends Resource
{
    protected static ?string $model = Rota::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Mapa::make('pontos')
                            ->label('Mapa da Rota')
                            ->columnSpan(5),

                        Grid::make(12)
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(7),

                                Forms\Components\Select::make('escola_id')
                                    ->label('Escola')
                                    ->required()
                                    ->relationship('escolas', 'nome')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpan(7),

                                Forms\Components\Select::make('turno')
                                    ->options([
                                        'Manhã' => 'Manhã',
                                        'Tarde' => 'Tarde',
                                        'Noite' => 'Noite',
                                        'Integral' => 'Integral',
                                    ])
                                    ->required()
                                    ->columnSpan(7),
                            ])
                            ->columnSpan(7),
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('turno'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRotas::route('/'),
            'create' => Pages\CreateRota::route('/create'),
            'edit' => Pages\EditRota::route('/{record}/edit'),
        ];
    }
}
