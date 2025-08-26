<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSelectionController extends Controller
{
    /**
     * Display the platform selection view.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/PlatformSelection');
    }
}
