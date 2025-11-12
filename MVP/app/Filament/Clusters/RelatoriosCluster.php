<?php

namespace App\Filament\Clusters;

use App\Services\UserService;
use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;

class RelatoriosCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $pluralModelLabel = 'Relatórios';
    protected static ?string $modelLabel = 'Relatório';
    protected static ?string $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 5;


    public static function canAccess(): bool
    {
        $user = Auth::user();

        if ($user && app(UserService::class)->ehAdmin($user)) {
            return true;
        }

        return false;
    }
}
