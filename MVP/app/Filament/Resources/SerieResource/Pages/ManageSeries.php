<?php

namespace App\Filament\Resources\SerieResource\Pages;

use App\Filament\Resources\SerieResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageSeries extends ManageRecords
{
    protected static string $resource = SerieResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('googleDrive')
                ->label('Google Drive')
                ->icon('heroicon-o-folder-open')
                ->color('primary')
                ->visible(function () {
                    /** @var \App\Models\User */
                    $user = Auth::user();

                    return $user?->hasGoogleOauth() ?? false;
                })
                ->modalHeading('My Google Drive')
                ->modalWidth('7xl')
                ->modalSubmitAction(false) // only a viewer
                ->modalCancelActionLabel('Close')
                ->modalContent(fn() => view('filament.modals.drive-files')),


            Actions\Action::make('googleConnect')
                ->label('Conectar com Google')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('success')
                ->url(route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']))
                ->visible(function () {
                    /** @var \App\Models\User */
                    $user = Auth::user();

                    return !$user?->hasGoogleOauth() ?? false;
                }),
        ];
    }
}