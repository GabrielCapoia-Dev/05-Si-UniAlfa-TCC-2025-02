<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComparativoMensalWidget;
use App\Filament\Widgets\CustoPorTurnoChart;
use App\Filament\Widgets\FiltroMesAnoWidget;
use App\Filament\Widgets\RotasMaisCarasChart;
use App\Filament\Widgets\TotalValorRotasWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Painel de Controle';
    protected static ?string $pluralModelLabel = 'Painel de Controle';
    protected static ?string $modelLabel = 'Painel de Controle';
    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ComparativoMensalWidget::class,
            RotasMaisCarasChart::class,
            CustoPorTurnoChart::class
        ];
    }
}
