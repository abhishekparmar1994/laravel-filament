<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ManageAppearance;
use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\ApplyUserTheme;
use App\Http\Middleware\SecurityHeaders;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemeManager;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
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
            ->brandName(fn (): string => (string) Setting::getValue('site_name', config('app.name')))
            ->spa(hasPrefetching: true)
            ->font('Plus Jakarta Sans')
            ->serifFont('Lora')
            ->login()
            ->profile()
            ->passwordReset()
            ->emailVerification(isRequired: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->colors(ThemeManager::colorsFor(ThemeManager::DEFAULT_THEME))
            ->userMenuItems([
                Action::make('appearance')
                    ->label('Theme & Appearance')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->url(fn (): string => ManageAppearance::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): HtmlString {
                    $user = auth()->user();
                    $theme = ThemeManager::resolveForUser($user instanceof User ? $user : null);

                    return new HtmlString(
                        '<script>document.documentElement.dataset.theme = '.Js::from($theme).';</script>',
                    );
                },
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): View => view('filament.components.panel-banners'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
                ApplyUserLocale::class,
                ApplyUserTheme::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
