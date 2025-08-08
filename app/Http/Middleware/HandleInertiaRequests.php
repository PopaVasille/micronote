<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => function () {
                return app()->getLocale();
            },
            'language' => function () {
                $locale = app()->getLocale();
                $jsonTranslations = base_path("lang/$locale.json");
                $phpTranslations = resource_path("lang/$locale");

                $translations = [];

                if (File::exists($jsonTranslations)) {
                    $translations = json_decode(File::get($jsonTranslations), true);
                }

                if (File::exists($phpTranslations)) {
                    foreach (File::allFiles($phpTranslations) as $file) {
                        $key = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                        $translations[$key] = require $file->getRealPath();
                    }
                }

                return $translations;
            },
        ]);
    }
}
