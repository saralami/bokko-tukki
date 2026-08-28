<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Send the authenticated user to the dashboard of their role.
     *
     * This is the shared post-login landing (Fortify home = /dashboard); each
     * role owns its own space, so we dispatch instead of showing a generic page.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        $route = match (true) {
            $user->hasUserRole(UserRole::Admin) => 'admin.dashboard',
            $user->hasUserRole(UserRole::Transporter) => 'transporter.dashboard',
            $user->hasUserRole(UserRole::Driver) => 'driver.dashboard',
            default => 'passenger.dashboard',
        };

        return redirect()->route($route);
    }
}
