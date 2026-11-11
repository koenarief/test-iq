<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
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
        return Inertia::render('Admin/Merchants/Create', [
            'instansiOptions' => Instansi::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instansi_id' => ['nullable', 'exists:instansi,id'],
            'is_active' => ['boolean'],
            'is_trial' => ['boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_if:is_trial,true'],
        ]);

        Merchant::create([
            'name' => $validated['name'],
            'instansi_id' => $validated['instansi_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_trial' => $validated['is_trial'] ?? false,
            'max_participants' => $validated['max_participants'] ?? null,
        ]);

        return redirect()
            ->route('admin.merchants.index')
            ->with('success', 'Merchant berhasil ditambahkan.');
    }

    public function edit(Merchant $merchant): Response
    {
        return Inertia::render('Admin/Merchants/Edit', [
            'merchant' => $merchant->setAttribute('participant_count', $merchant->participantCount()),
            'instansiOptions' => Instansi::query()->orderBy('nama')->get(['id', 'nama']),
            'startUrl' => route('assessment.start.merchant', $merchant),
        ]);
    }

    public function update(Request $request, Merchant $merchant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instansi_id' => ['nullable', 'exists:instansi,id'],
            'is_active' => ['boolean'],
            'is_trial' => ['boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100000', 'required_if:is_trial,true'],
        ]);

        $merchant->update([
            'name' => $validated['name'],
            'instansi_id' => $validated['instansi_id'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'is_trial' => $validated['is_trial'] ?? false,
            'max_participants' => $validated['max_participants'] ?? null,
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
