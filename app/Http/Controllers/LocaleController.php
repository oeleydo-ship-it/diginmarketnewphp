<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** Persist the choice on the user when signed in, and always in the session so guests keep it too. */
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, (array) config('locales.available')), 404);
        $request->session()->put('locale', $locale);
        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        }

        return back();
    }
}
