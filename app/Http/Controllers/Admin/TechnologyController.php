<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTechnologyRequest;
use App\Http\Requests\Admin\UpdateTechnologyRequest;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    /**
     * Display a listing of the technologies.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Technology::class);

        $technologies = Technology::query()
            ->withCount(['requirements', 'candidates'])
            ->orderBy('group')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Technologies/Index', [
            'technologies' => $technologies,
        ]);
    }

    /**
     * Show the form for creating a new technology.
     */
    public function create(): Response
    {
        $this->authorize('create', Technology::class);

        return Inertia::render('Admin/Technologies/Create', [
            'groups' => $this->existingGroups(),
        ]);
    }

    /**
     * Store a newly created technology in storage.
     */
    public function store(StoreTechnologyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Technology::create([
            'name' => $validated['name'],
            'group' => $validated['group'] ?? null,
            'synonyms' => $validated['synonyms'] ?? [],
        ]);

        return to_route('admin.technologies.index')->with('success', 'Технология добавлена.');
    }

    /**
     * Show the form for editing the specified technology.
     */
    public function edit(Technology $technology): Response
    {
        $this->authorize('update', $technology);

        return Inertia::render('Admin/Technologies/Edit', [
            'technology' => [
                'id' => $technology->id,
                'name' => $technology->name,
                'group' => $technology->group,
                'synonyms' => $technology->synonyms ?? [],
            ],
            'groups' => $this->existingGroups(),
        ]);
    }

    /**
     * Update the specified technology in storage.
     */
    public function update(UpdateTechnologyRequest $request, Technology $technology): RedirectResponse
    {
        $validated = $request->validated();

        $technology->update([
            'name' => $validated['name'],
            'group' => $validated['group'] ?? null,
            'synonyms' => $validated['synonyms'] ?? [],
        ]);

        return to_route('admin.technologies.index')->with('success', 'Технология обновлена.');
    }

    /**
     * Remove the specified technology from storage.
     */
    public function destroy(Technology $technology): RedirectResponse
    {
        $this->authorize('delete', $technology);

        $requirementsCount = $technology->requirements()->count();
        $candidatesCount = $technology->candidates()->count();

        if ($requirementsCount > 0 || $candidatesCount > 0) {
            return back()->with(
                'error',
                "Нельзя удалить технологию: используется в требованиях ({$requirementsCount}) или у кандидатов ({$candidatesCount})."
            );
        }

        $technology->delete();

        return to_route('admin.technologies.index')->with('success', 'Технология удалена.');
    }

    /**
     * Get the distinct list of existing groups, for the group autocomplete/select.
     *
     * @return array<int, string>
     */
    protected function existingGroups(): array
    {
        return Technology::query()
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();
    }
}