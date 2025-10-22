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
use App\Forms\Components\OrdenarParadas;
use Filament\Tables\Table;
use App\Models\Escola;
use Illuminate\Support\Facades\Auth;
use App\Models\PontosDeParada;
use App\Services\RotaService;

class RotaResource extends Resource
{
    protected static ?string $model = Rota::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Rotas';
    protected static ?string $pluralModelLabel = 'Rotas';
    protected static ?string $modelLabel = 'Rota';
    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return app(RotaService::class)->configurarFormulario($form);
    }

    public static function table(Table $table): Table
    {
        return app(RotaService::class)->configurarTabela($table, Auth::user());
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
