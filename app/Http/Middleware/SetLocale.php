<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the active locale in priority order: the signed-in user's saved preference, then the
     * session (guest switcher), then the browser's Accept-Language, then the configured default.
     * An unsupported value at any step falls through rather than breaking the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys((array) config('locales.available'));
        $locale = $this->firstSupported([
            $request->user()?->locale,
            $request->session()->get('locale'),
            $request->getPreferredLanguage($available),
        ], $available) ?? (string) config('locales.default');
        app()->setLocale($locale);

        return $next($request);
    }

    /** @param array<int,?string> $candidates @param list<string> $available */
    private function firstSupported(array $candidates, array $available): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate && in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
