<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCannedResponseRequest;
use App\Http\Requests\UpdateCannedResponseRequest;
use App\Models\CannedResponse;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CannedResponseController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CannedResponse::class);

        return Inertia::render('Panel/CannedResponses/Index', [
            'cannedResponses' => CannedResponse::query()
                ->with(['department:id,name', 'author:id,name'])
                ->orderBy('title')
                ->get(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCannedResponseRequest $request): RedirectResponse
    {
        CannedResponse::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Respuesta predefinida creada.');
    }

    public function update(UpdateCannedResponseRequest $request, CannedResponse $cannedResponse): RedirectResponse
    {
        $this->authorize('update', $cannedResponse);

        $cannedResponse->update($request->validated());

        return back()->with('success', 'Respuesta predefinida actualizada.');
    }

    public function destroy(CannedResponse $cannedResponse): RedirectResponse
    {
        $this->authorize('delete', $cannedResponse);

        $cannedResponse->delete();

        return back()->with('success', 'Respuesta predefinida eliminada.');
    }
}
