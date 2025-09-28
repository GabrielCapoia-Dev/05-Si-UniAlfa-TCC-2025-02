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

                    if (!$user) {
                        return false;
                    }

                    if ($user->hasGoogleOauth() && $user->hasRole('Admin')) {
                        return true;
                    }

                    return false;
                })
                ->modalHeading('Seletor de Planilhas')
                ->modalWidth('md')
                ->modalContent(fn() => view('livewire.drive-file', [
                    'modelClass' => static::getModel(),
                ])),


            Actions\Action::make('googleConnect')
                ->label('Conectar com Google')
                ->icon(fn() => new HtmlString(@file_get_contents(public_path('images/google-drive-icon.svg')) ?: ''))
                ->color('gray')
                ->visible(function () {
                    /** @var \App\Models\User */
                    $user = Auth::user();

                    if (!$user) {
                        return false;
                    }

                    if (!$user->hasGoogleOauth() && $user->hasRole('Admin')) {
                        return true;
                    }

                    return false;
                })
                ->url(route('google.redirect')),

            Actions\CreateAction::make(),
        ];
    }
}