<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComparativoMensalWidget;
use App\Filament\Widgets\CustoPorTurnoChart;
use App\Filament\Widgets\EscolasMaisCarasChart;
use App\Filament\Widgets\RotasMaisCarasChart;
use App\Services\UserService;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Painel de Controle';
    protected static ?string $pluralModelLabel = 'Painel de Controle';
    protected static ?string $modelLabel = 'Painel de Controle';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $navigationGroup = "Acesso";
    protected static ?int $navigationSort = 3;


    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && app(UserService::class)->ehAdmin($user);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ComparativoMensalWidget::class,
            RotasMaisCarasChart::class,
            CustoPorTurnoChart::class,
            EscolasMaisCarasChart::class,
        ];
    }
}
