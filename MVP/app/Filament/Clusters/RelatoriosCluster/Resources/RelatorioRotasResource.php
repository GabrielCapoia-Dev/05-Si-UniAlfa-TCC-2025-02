<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources;

use App\Filament\Clusters\RelatoriosCluster;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioRotasResource\Pages;
use App\Models\Rota;
use App\Services\RelatorioRotaService;
use Filament\Resources\Resource;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RelatorioRotasResource extends Resource
{
    protected static ?string $model = Rota::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $cluster = RelatoriosCluster::class;
    protected static ?string $slug = 'rotas';
    protected static ?string $pluralModelLabel = 'Custos Rotas';

    public static function table(Table $table): Table
    {
        return app(RelatorioRotaService::class)
            ->configurarTabelaRotas($table, Auth::user());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRelatorioRotas::route('/'),
        ];
    }
}
