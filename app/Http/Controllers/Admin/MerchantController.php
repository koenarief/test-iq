<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->value() ?: null;

        $merchants = Merchant::query()
            ->withCount(['istTests', 'discTests'])
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Merchants/Index', [
            'merchants' => $merchants,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Merchants/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Merchant::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.merchants.index')
            ->with('success', 'Merchant berhasil ditambahkan.');
    }

    public function edit(Merchant $merchant): Response
    {
        return Inertia::render('Admin/Merchants/Edit', [
            'merchant' => $merchant,
            'startUrls' => [
                'ist' => route('ist.index.merchant', $merchant),
                'disc' => route('disc.index.merchant', $merchant),
            ],
        ]);
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $merchant->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return redirect()
            ->route('admin.merchants.index')
            ->with('success', 'Merchant berhasil diperbarui.');
    }

    public function destroy(Merchant $merchant): RedirectResponse
    {
        $merchant->delete();

        return redirect()
            ->route('admin.merchants.index')
            ->with('success', 'Merchant berhasil dihapus.');
    }
}
