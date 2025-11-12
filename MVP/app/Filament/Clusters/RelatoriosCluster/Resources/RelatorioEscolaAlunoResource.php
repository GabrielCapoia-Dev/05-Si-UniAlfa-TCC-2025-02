<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources;

use App\Filament\Clusters\RelatoriosCluster;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource\Pages;
use App\Models\Escola;
use App\Services\RelatorioEscolaAlunosService;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\SubNavigationPosition;

class RelatorioEscolaAlunoResource extends Resource
{
    protected static ?string $model = Escola::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = RelatoriosCluster::class;
    protected static ?string $pluralModelLabel = 'Custo por Aluno';
    protected static ?string $slug = 'escolas-alunos';

    public static function table(Table $table): Table
    {
        return app(RelatorioEscolaAlunosService::class)
            ->configurarTabela($table, Auth::user());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRelatorioEscolaAlunos::route('/'),
        ];
    }
}
