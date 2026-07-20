<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use SebastianBergmann\Type\FalseType;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->topbar(False)
            ->globalSearch(position: \Filament\Enums\GlobalSearchPosition::Topbar)
            ->defaultThemeMode(ThemeMode::Light)
            ->font('Plus Jakarta Sans', provider: GoogleFontProvider::class)

            // --- Tampilan Panel ---
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('LAB EKU SULSEL')

            ->brandName('LAB EKU SULSEL')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->colors([
                'primary' => Color::Hex('#054177'),
                'gray' => Color::Hex('#F5F5F5'),
            ])

            // --- Registrasi Resources ---
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                EkuTransactionResource::class,
            ])

            // --- Registrasi Pages ---
            // Sengaja TIDAK pakai ->discoverPages(), karena folder Filament/Pages
            // juga berisi Pages/Auth/Login.php yang sudah didaftarkan khusus lewat
            // ->login() di atas. Kalau di-auto-discover juga sebagai page biasa,
            // route-nya bisa bentrok / duplikat.
            ->pages([
                Dashboard::class,
            ])

            // --- Middleware Configuration ---
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
