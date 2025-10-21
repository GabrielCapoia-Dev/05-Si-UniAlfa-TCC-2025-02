<?php

namespace App\Providers\Filament;

use App\Http\Controllers\ExportModeloController;
use App\Http\Controllers\CarteirinhaPrintController;
use App\Livewire\PasswordReset;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use App\Livewire\LoginPage;
use App\Http\Middleware\ValidaUser;
use App\Services\UserService;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->routes(function () {
                Route::middleware(['valida.user'])->group(function () {
                    Route::get('/exportar-modelo', [ExportModeloController::class, 'handle'])
                        ->name('exportar-modelo');
                });
                Route::get('/carteirinhas', CarteirinhaPrintController::class)
                    ->name('carteirinhas');
            })
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(LoginPage::class)
            ->colors([
                'primary' => Color::Green,
                'gray' => [
                    50 => '#e9f0f0ff',
                    100 => '#c7f8e9c7',
                    200 => '#c0d4d4ff',
                    300 => '#c7caccff',
                    400 => '#a0a0a0ff',
                    500 => '#929292ff',
                    600 => '#5c5c66ff',
                    700 => '#513737ff',
                    800 => '#1f2937',
                    900 => '#0e1930ff',
                    950 => '#081124ff',
                ],
            ])
            ->brandLogo(fn() => view('components.logo'))
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ValidaUser::class
            ])
            ->plugins([


                ActivitylogPlugin::make()
                    ->label('Registro de Atividade')
                    ->pluralLabel('Registro de Atividades')
                    ->navigationGroup('Administrativo')
                    ->navigationSort(1)
                    ->authorize(fn() => app(UserService::class)->ehAdmin(Auth::user())),
            ]);
    }
}
