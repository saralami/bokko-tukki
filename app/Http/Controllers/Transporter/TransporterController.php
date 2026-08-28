<?php

namespace App\Http\Controllers\Transporter;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTransporterRequest;
use App\Models\Transporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransporterController extends Controller
{
    /**
     * Show the form for editing the authenticated transporter's company.
     */
    public function edit(Request $request): Response
    {
        $transporter = $request->user()->transporter ?? abort(403);

        return Inertia::render('transporter/Company', [
            'transporter' => $transporter,
        ]);
    }

    /**
     * Update the given transporter company owned by the authenticated user.
     */
    public function update(UpdateTransporterRequest $request, Transporter $transporter): RedirectResponse
    {
        $transporter->update($request->validated());

        return to_route('transporter.dashboard');
    }
}
