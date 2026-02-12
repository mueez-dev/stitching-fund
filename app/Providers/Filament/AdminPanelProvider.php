<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Auth\Register;
use App\Http\Middleware\CheckUserStatus;
use App\Filament\Widgets\SystemHealthWidget;
use App\Filament\Widgets\InvestmentPoolWidget;
use App\Filament\Widgets\SecurityAlertsWidget;
use App\Filament\Widgets\SuperAdminStatsWidget;
use App\Filament\Widgets\InvestorStatsWidget;
use App\Filament\Widgets\AgencyOwnerStatsWidget;
use App\Filament\Widgets\InvestmentPerformanceWidget;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use STS\FilamentImpersonate\Actions\Impersonate;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->registration(Register::class)
            ->passwordReset()
            ->brandName('ZARYQ')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3.5rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->renderHook(
                'panels::head.end',
                fn (): string => '
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                    <link rel="icon" type="image/png" href="' . asset('images/logo.png') . '">
                    <link rel="icon" type="image/png" sizes="32x32" href="' . asset('images/logo.png') . '">
                    <link rel="icon" type="image/png" sizes="16x16" href="' . asset('images/logo.png') . '">
                    <link rel="apple-touch-icon" sizes="180x180" href="' . asset('images/logo.png') . '">
                    <style>
                        .filament-branding img {
                            border-radius: 12px !important;
                            box-shadow: 0 6px 6px rgba(0, 0, 0, 0.1) !important;
                            max-width: 80px !important;
                            height: auto !important;
                        }
                    </style>
                ',
            )
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverResources(in: app_path('Filament/Resources/UserInvitation'), for: 'App\\Filament\\Resources\\UserInvitation')
            ->plugins([
                //
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
               SuperAdminStatsWidget::class,
                SystemHealthWidget::class,
               SecurityAlertsWidget::class,
               InvestorStatsWidget::class,
               AgencyOwnerStatsWidget::class,
               InvestmentPerformanceWidget::class,
               InvestmentPoolWidget::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
                CheckUserStatus::class,
            ], isPersistent: true)
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
            ]);
    }
}
