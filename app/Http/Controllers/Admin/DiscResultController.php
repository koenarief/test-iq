<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscTest;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscResultController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: null;
        $search = $request->string('search')->value() ?: null;
        $merchantId = $request->filled('merchant_id') ? $request->integer('merchant_id') : null;

        $tests = DiscTest::query()
            ->with(['profile:id,code,name', 'merchant:id,name'])
            ->select([
                'id', 'participant_name', 'age', 'gender', 'status',
                'disc_type', 'primary_type', 'secondary_type', 'disc_profile_id',
                'started_at', 'finished_at', 'created_at', 'merchant_id',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->when($merchantId !== null, fn ($query) => $merchantId === 0
                ? $query->whereNull('merchant_id')
                : $query->where('merchant_id', $merchantId))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/DiscResults/Index', [
            'tests' => $tests,
            'statuses' => ['draft', 'in_progress', 'completed'],
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'merchant_id' => $merchantId,
            ],
        ]);
    }

    public function show(DiscTest $discTest): Response
    {
        $discTest->load(['profile', 'merchant:id,name']);

        return Inertia::render('Admin/DiscResults/Show', [
            'test' => [
                ...$discTest->only([
                    'id', 'participant_name', 'age', 'gender', 'status', 'started_at', 'finished_at',
                    'most_d', 'most_i', 'most_s', 'most_c',
                    'least_d', 'least_i', 'least_s', 'least_c',
                    'change_d', 'change_i', 'change_s', 'change_c',
                    'graph_d', 'graph_i', 'graph_s', 'graph_c',
                    'most_graph_d', 'most_graph_i', 'most_graph_s', 'most_graph_c',
                    'least_graph_d', 'least_graph_i', 'least_graph_s', 'least_graph_c',
                    'primary_type', 'secondary_type', 'disc_type',
                ]),
                'merchant_name' => $discTest->merchant?->name,
            ],
            'profile' => $discTest->profile,
        ]);
    }
}
