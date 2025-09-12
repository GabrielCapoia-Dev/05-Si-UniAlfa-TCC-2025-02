<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurmaResource\Pages;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TurmaResource extends Resource
{
    protected static ?string $model = Turma::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Turmas';
    protected static ?string $pluralModelLabel = 'Turmas';
    protected static ?string $modelLabel = 'Turma';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_escola')
                    ->label('Escola')
                    ->relationship('escola', 'nome')
                    ->required()
                    ->preload()
                    ->searchable(),

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
                    ->rule(fn($get, $record) => 
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
                        'Integral' => 'Integral',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ManageTurmas::route('/'),
        ];
    }
}
