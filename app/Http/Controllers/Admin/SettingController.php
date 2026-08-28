<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display the editable business settings.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/Settings', [
            'settings' => Settings::all(),
        ]);
    }

    /**
     * Update the business settings, auditing each change.
     */
    public function update(Request $request): RedirectResponse
    {
        // Setting keys contain dots (e.g. "commission.rate"), so validate the
        // flat array generically then range-check each value by hand to keep the
        // literal keys intact.
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['required', 'numeric'],
        ]);

        $schema = Settings::schema();
        $values = (array) $request->input('settings');
        $errors = [];

        foreach ($schema as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $new = (float) $values[$key];

            if ($new < $definition['min'] || $new > $definition['max']) {
                $errors["settings.{$key}"] = "La valeur doit être comprise entre {$definition['min']} et {$definition['max']}.";

                continue;
            }

            $previous = Settings::get($key);

            if ($new === (float) $previous) {
                continue;
            }

            Settings::set($key, $definition['type'] === 'int' ? (int) $new : $new);

            AuditLogger::log(
                'setting.updated',
                "Paramètre « {$definition['label']} » : {$previous} → {$new}.",
                null,
                ['key' => $key, 'from' => $previous, 'to' => $new],
            );
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return back();
    }
}
