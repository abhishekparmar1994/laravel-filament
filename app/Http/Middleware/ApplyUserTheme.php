<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ThemeManager;
use Closure;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserTheme
{
    /**
     * Register the authenticated user's selected Filament color palette for this request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $theme = ThemeManager::resolveForUser($user instanceof User ? $user : null);

        FilamentColor::register(ThemeManager::colorsFor($theme));

        return $next($request);
    }
}
