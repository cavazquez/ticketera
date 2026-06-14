<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Inertia\Inertia;
use Inertia\Response;

class SystemHealthController extends Controller
{
    public function index(SystemHealthService $health): Response
    {
        return Inertia::render('Panel/SystemHealth/Index', [
            'report' => $health->run(),
        ]);
    }
}
