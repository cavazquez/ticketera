<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:'.implode(',', LocaleManager::SUPPORTED)],
        ]);

        $request->session()->put('locale', $validated['locale']);
        LocaleManager::apply($validated['locale'], $request);

        return back();
    }
}
