<?php

namespace App\Http\Controllers\Panel;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Department;
use App\Models\User;
use App\Support\EnumOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($this->requireUser($request)->isAdmin(), 403);

        return Inertia::render('Panel/Agents/Index', [
            'users' => User::query()
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'department_id']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => EnumOptions::from(UserRole::class),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($this->requireUser($request)->isStaff(), 403);

        $search = $request->string('q')->trim()->toString();

        $query = User::query()
            ->whereIn('role', [UserRole::Agent, UserRole::Admin])
            ->orderBy('name')
            ->limit(15);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->get(['id', 'name', 'email'])
        );
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->except('password', 'password_confirmation'),
            'password' => Hash::make($request->validated('password')),
            'email_verified_at' => now(),
            'department_id' => $request->validated('department_id'),
        ]);

        return back()->with('success', 'Usuario creado.');
    }

    public function update(UpdateAgentRequest $request, User $agent): RedirectResponse
    {
        abort_unless($this->requireUser($request)->isAdmin(), 403);

        $agent->update($request->validated());

        return back()->with('success', 'Usuario actualizado.');
    }
}
