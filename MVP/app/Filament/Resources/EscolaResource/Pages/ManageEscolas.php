<?php

namespace App\Filament\Resources\EscolaResource\Pages;

use App\Filament\Resources\EscolaResource;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

use Filament\Resources\Pages\ManageRecords;

class ManageEscolas extends ManageRecords
{
    protected static string $resource = EscolaResource::class;

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
                ->modalContent(fn() => view('filament.modals.drive-files', [
                    'modelClass' => static::getModel(),
                ])),


            Actions\Action::make('googleConnect')
                ->label('Conectar com Google')
                ->icon(fn() => new HtmlString(@file_get_contents(public_path('images/google-drive-icon.svg')) ?: ''))
                ->color('gray')
                ->url(route('google.redirect'))
                ->visible(fn() => !Auth::user()?->google_token),

            Actions\CreateAction::make(),
        ];
    }
}