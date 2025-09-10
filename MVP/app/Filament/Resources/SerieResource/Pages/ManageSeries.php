<?php

namespace App\Filament\Resources\SerieResource\Pages;

use App\Filament\Resources\SerieResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ManageSeries extends ManageRecords
{
    protected static string $resource = SerieResource::class;


    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('googleDrive')
                ->label('Google Drive')
                ->icon(fn() => new HtmlString(
                    @file_get_contents(public_path('images/google-drive-icon.svg')) ?: ''
                ))
                ->color('gray')
                ->visible(function () {
                    /** @var \App\Models\User */
                    $user = Auth::user();

                    return $user?->hasGoogleOauth() ?? false;
                })
                ->modalHeading('Seletor de Planilhas')
                ->modalWidth('md')
                ->modalContent(fn() => view('filament.modals.drive-files')),


            Actions\Action::make('googleConnect')
                ->label('Conectar com Google')
                ->icon(fn() => new HtmlString(
                    @file_get_contents(public_path('images/google-drive-icon.svg')) ?: ''
                ))
                ->color('gray')
                ->url(route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']))
                ->visible(function () {
                    /** @var \App\Models\User */
                    $user = Auth::user();

                    return !$user?->hasGoogleOauth() ?? false;
                }),

            Actions\CreateAction::make(),

        ];
    }
}
