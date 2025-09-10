<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PontosDeParadaResource\Pages;
use App\Filament\Resources\PontosDeParadaResource\RelationManagers;
use App\Models\PontosDeParada;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Field;
use App\Forms\Components\LeafletMap;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Grid;


class PontosDeParadaResource extends Resource
{

    protected static ?string $model = PontosDeParada::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    
    
    public static function form(Form $form): Form
    {
        
        $defaultRaius = 500;


        return $form->schema([
            Grid::make(2)
                ->schema([
                    LeafletMap::make('map_data')
                        ->label('Localização')
                        ->columnSpan(1)
                        ->defaultRadius($defaultRaius)
                        ->default(fn(Get $get) => [
                            'lat' => $get('latitude') ?: -23.7637,
                            'lng' => $get('longitude') ?: -53.2967,
                        ]),
                    Grid::make(1)
                        ->schema([
                            Forms\Components\TextInput::make('nome')
                                ->required()
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('latitude')
                                        ->required()
                                        ->visible(false)
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('longitude')
                                        ->required()
                                        ->visible(false)
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('raio')
                                        ->required()
                                        ->visible(false)
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('logradouro')->required(),
                                    Forms\Components\TextInput::make('bairro')->required(),
                                    Forms\Components\TextInput::make('cidade')->required(),
                                    Forms\Components\TextInput::make('estado')->required(),
                                    Forms\Components\TextInput::make('cep')->required(),
                                ]),
                        ])->columnSpan(1)

                ]),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->searchable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->searchable(),
                Tables\Columns\TextColumn::make('raio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logradouro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bairro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cep')
                    ->searchable(),
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
            'index' => Pages\ListPontosDeParadas::route('/'),
            'create' => Pages\CreatePontosDeParada::route('/create'),
            'edit' => Pages\EditPontosDeParada::route('/{record}/edit'),
        ];
    }
}