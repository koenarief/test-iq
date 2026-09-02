<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\DiscTest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscResultController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: null;
        $search = $request->string('search')->value() ?: null;

        $tests = DiscTest::query()
            ->with(['profile:id,code,name'])
            ->select([
                'id', 'participant_name', 'age', 'gender', 'status',
                'disc_type', 'primary_type', 'secondary_type', 'disc_profile_id',
                'started_at', 'finished_at', 'created_at', 'merchant_id',
            ])
            ->where('merchant_id', $request->user()->merchant_id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where('participant_name', 'like', '%'.$search.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Merchant/DiscResults/Index', [
            'tests' => $tests,
            'statuses' => ['draft', 'in_progress', 'completed'],
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, DiscTest $discTest): Response
    {
        abort_unless($discTest->merchant_id === $request->user()->merchant_id, 404);

        $discTest->load('profile');

        return Inertia::render('Merchant/DiscResults/Show', [
            'test' => $discTest->only([
                'id', 'participant_name', 'age', 'gender', 'status', 'started_at', 'finished_at',
                'most_d', 'most_i', 'most_s', 'most_c',
                'least_d', 'least_i', 'least_s', 'least_c',
                'change_d', 'change_i', 'change_s', 'change_c',
                'graph_d', 'graph_i', 'graph_s', 'graph_c',
                'most_graph_d', 'most_graph_i', 'most_graph_s', 'most_graph_c',
                'least_graph_d', 'least_graph_i', 'least_graph_s', 'least_graph_c',
                'primary_type', 'secondary_type', 'disc_type',
            ]),
            'profile' => $discTest->profile,
        ]);
    }
}
