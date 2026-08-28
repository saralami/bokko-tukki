<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterTransporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTransporterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class TransporterRegisterController extends Controller
{
    /**
     * Show the transporter (company) registration form.
     */
    public function create(): Response
    {
        return Inertia::render('auth/RegisterTransporter', [
            'passwordRules' => Password::default(),
        ]);
    }

    /**
     * Register a new transport company (pending admin validation) and sign in.
     */
    public function store(RegisterTransporterRequest $request, RegisterTransporter $registerTransporter): RedirectResponse
    {
        $user = $registerTransporter($request->validated());

        Auth::login($user);

        return to_route('transporter.dashboard');
    }
}
