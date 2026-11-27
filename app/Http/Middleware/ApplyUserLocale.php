<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserLocale
{
    /**
     * @var array<int, string>
     */
    public const SUPPORTED_LOCALES = ['en', 'es', 'fr', 'hi'];

    /**
     * Apply the authenticated user's preferred locale for the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && in_array($user->locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
