<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use App\Support\EnumOptions;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $metrics): Response
    {
        $user = $this->requireUser($request);

        if ($user->isStaff()) {
            return Inertia::render('Dashboard', [
                'metrics' => $metrics->forUser($user),
                'statuses' => EnumOptions::from(TicketStatus::class, true),
            ]);
        }

        return Inertia::render('Dashboard', [
            'metrics' => null,
            'statuses' => [],
        ]);
    }
}
