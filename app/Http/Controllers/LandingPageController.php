<?php

namespace App\Http\Controllers;

use App\Models\EarlyAccessSignup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function show()
    {
        return Inertia::render('Landing');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:early_access_signups,email',
        ]);

        EarlyAccessSignup::create($request->only('email'));

        return redirect()->back()->with('success', 'Mulțumim pentru înscriere! Te vom anunța când lansăm.');
    }
}