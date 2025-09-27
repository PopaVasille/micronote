<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);

        // Store the determined locale in session for consistency
        if (! session()->has('locale')) {
            session()->put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Determine the appropriate locale for the request
     */
    private function determineLocale(Request $request): string
    {
        $supportedLocales = ['en', 'ro'];

        // 1. Check if locale is explicitly set in session
        if (session()->has('locale') && in_array(session()->get('locale'), $supportedLocales)) {
            return session()->get('locale');
        }

        // 2. Check browser's Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $browserLocales = $this->parseBrowserLocales($acceptLanguage);

            foreach ($browserLocales as $browserLocale) {
                if (in_array($browserLocale, $supportedLocales)) {
                    return $browserLocale;
                }
            }
        }

        // 3. Fall back to app default
        return config('app.locale', 'en');
    }

    /**
     * Parse browser Accept-Language header
     */
    private function parseBrowserLocales(string $acceptLanguage): array
    {
        $locales = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            $locale = substr($part, 0, 2); // Get just the language code (e.g., 'en' from 'en-US')

            if (strlen($locale) === 2 && ctype_alpha($locale)) {
                $locales[] = strtolower($locale);
            }
        }

        return array_unique($locales);
    }
}
