<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->path('/')
            ->brandName('Osfero')
            ->favicon(asset('images/orbixsphere_logo.png'))
            // ->brandLogo(fn () => view('filament.brand'))
            // ->brandLogoHeight('auto')
            ->login()
            ->registration(\App\Filament\Auth\Register::class)
            ->passwordReset()
            ->spa()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => '<style>:root { --fi-topbar-height: 20rem; }</style>',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->tenant(\App\Models\Tenant::class, slugAttribute: 'slug')
            ->tenantMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Join Another Space')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn (): string => \App\Filament\Pages\JoinWorkspace::getUrl()),
            ])
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->pages([
                Dashboard::class,
                \App\Filament\Resources\Trackings\Pages\Tracking::class,
            ])
            ->tenantMiddleware([
                \App\Http\Middleware\UpdateLastActiveTenant::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
            ]);
    }
}
