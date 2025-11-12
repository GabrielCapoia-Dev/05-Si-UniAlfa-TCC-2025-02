<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources;

use App\Filament\Clusters\RelatoriosCluster;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolasResource\Pages;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolasResource\RelationManagers;
use App\Models\Escola;
use App\Services\RelatorioService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Facades\Auth;

class RelatorioEscolasResource extends Resource
{
    protected static ?string $model = Escola::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $cluster = RelatoriosCluster::class;
    protected static ?string $slug = 'escolas';
    protected static ?string $pluralModelLabel = 'Custos Escolas';
    protected static ?string $navigationGroup = 'Custo Escola';

    protected static function relatorio(): RelatorioService
    {
        return app(RelatorioService::class);
    }

    public static function table(Table $table): Table
    {
        return static::relatorio()->configurarTabela($table, Auth::user());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRelatorioEscolas::route('/'),
        ];
    }
}
