<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\DevAutoLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->registration(Register::class)
            ->brandName('Solas Rún')
            ->colors([
                'primary' => [
                    50 => 'oklch(0.97 0.014 254)',
                    100 => 'oklch(0.94 0.028 254)',
                    200 => 'oklch(0.88 0.056 254)',
                    300 => 'oklch(0.79 0.098 254)',
                    400 => 'oklch(0.68 0.155 254)',
                    500 => 'oklch(0.58 0.213 264)',
                    600 => 'oklch(0.52 0.235 264)',
                    700 => 'oklch(0.46 0.216 264)',
                    800 => 'oklch(0.39 0.180 264)',
                    900 => 'oklch(0.34 0.144 264)',
                    950 => 'oklch(0.25 0.105 264)',
                ],
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->navigationGroups([
                NavigationGroup::make('Today')
                    ->icon('heroicon-o-sun')
                    ->collapsed(false),
                NavigationGroup::make('Goals & Projects')
                    ->icon('heroicon-o-rocket-launch')
                    ->collapsed(false),
                NavigationGroup::make('Habits')
                    ->icon('heroicon-o-arrow-path')
                    ->collapsed(true),
                NavigationGroup::make('Journal')
                    ->icon('heroicon-o-book-open')
                    ->collapsed(true),
                NavigationGroup::make('Progress')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),
                NavigationGroup::make('AI Studio')
                    ->icon('heroicon-o-sparkles')
                    ->collapsed(true),
                NavigationGroup::make('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
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
                DevAutoLogin::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
