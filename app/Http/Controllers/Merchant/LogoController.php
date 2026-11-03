<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $merchant = $request->user()->merchant;
        $oldPath = $merchant->logo_path;

        $merchant->update([
            'logo_path' => $request->file('logo')->store('merchant-logos', 'public'),
        ]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Logo berhasil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $merchant = $request->user()->merchant;

        if ($merchant->logo_path) {
            Storage::disk('public')->delete($merchant->logo_path);
            $merchant->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo dihapus.');
    }
}
