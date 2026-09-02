<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $merchant = $request->user()->merchant;
        $merchantId = $merchant->id;

        return Inertia::render('Merchant/Dashboard', [
            'stats' => [
                'ist_total' => IstTest::query()->where('merchant_id', $merchantId)->count(),
                'ist_completed' => IstTest::query()->where('merchant_id', $merchantId)
                    ->where('status', IstTest::STATUS_COMPLETED)->count(),
                'disc_total' => DiscTest::query()->where('merchant_id', $merchantId)->count(),
                'disc_completed' => DiscTest::query()->where('merchant_id', $merchantId)
                    ->where('status', 'completed')->count(),
            ],
            'startUrls' => [
                'ist' => route('ist.index.merchant', $merchant),
                'disc' => route('disc.index.merchant', $merchant),
            ],
        ]);
    }
}
