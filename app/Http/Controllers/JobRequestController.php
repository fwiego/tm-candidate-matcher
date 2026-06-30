<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequestRequest;
use App\Http\Requests\UpdateJobRequestRequest;
use App\Models\JobRequest;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    /**
     * Display a listing of the requests, with filters.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobRequest::class);

        $requests = JobRequest::query()
            ->with('creator:id,name')
            ->withCount('requirements')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('grade'), fn ($q) => $q->where('grade', $request->input('grade')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Requests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['status', 'grade', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new request.
     */
    public function create(): Response
    {
        $this->authorize('create', JobRequest::class);

        return Inertia::render('Requests/Create', [
            'technologies' => Technology::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'group']),
            'grades' => JobRequest::GRADES,
        ]);
    }

    /**
     * Store a newly created request in storage.
     */
    public function store(StoreJobRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $jobRequest = DB::transaction(function () use ($validated, $request) {
            $jobRequest = JobRequest::create([
                'position' => $validated['position'],
                'description' => $validated['description'] ?? null,
                'grade' => $validated['grade'],
                'location' => $validated['location'] ?? null,
                'citizenship' => $validated['citizenship'] ?? null,
                'needed_by' => $validated['needed_by'] ?? null,
                'status' => $validated['status'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['requirements'] ?? [] as $req) {
                $jobRequest->requirements()->create([
                    'technology_id' => $req['technology_id'],
                    'type' => $req['type'],
                    'weight' => $req['weight'],
                ]);
            }

            return $jobRequest;
        });

        return to_route('requests.show', $jobRequest)->with('success', 'Запрос создан.');
    }

    /**
     * Display the specified request.
     */
    public function show(JobRequest $jobRequest): Response
    {
        $this->authorize('view', $jobRequest);

        $jobRequest->load(['creator:id,name', 'requirements.technology']);

        return Inertia::render('Requests/Show', [
            'request' => $jobRequest,
            'canEdit' => $jobRequest->status !== 'closed'
                && (auth()->user()->isAdmin() || $jobRequest->created_by === auth()->id()),
        ]);
    }

    /**
     * Show the form for editing the specified request.
     */
    public function edit(JobRequest $jobRequest): Response
    {
        $this->authorize('update', $jobRequest);

        $jobRequest->load('requirements');

        return Inertia::render('Requests/Edit', [
            'request' => [
                'id' => $jobRequest->id,
                'position' => $jobRequest->position,
                'description' => $jobRequest->description,
                'grade' => $jobRequest->grade,
                'location' => $jobRequest->location,
                'citizenship' => $jobRequest->citizenship,
                'needed_by' => $jobRequest->needed_by?->format('Y-m-d'),
                'status' => $jobRequest->status,
                'requirements' => $jobRequest->requirements->map(fn ($r) => [
                    'technology_id' => $r->technology_id,
                    'type' => $r->type,
                    'weight' => $r->weight,
                ]),
            ],
            'technologies' => Technology::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'group']),
            'grades' => JobRequest::GRADES,
            'statuses' => JobRequest::STATUSES,
        ]);
    }

    /**
     * Update the specified request in storage.
     */
    public function update(UpdateJobRequestRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $jobRequest) {
            $jobRequest->update([
                'position' => $validated['position'],
                'description' => $validated['description'] ?? null,
                'grade' => $validated['grade'],
                'location' => $validated['location'] ?? null,
                'citizenship' => $validated['citizenship'] ?? null,
                'needed_by' => $validated['needed_by'] ?? null,
                'status' => $validated['status'],
            ]);

            // Sync requirements: simplest correct approach is replace-all within a transaction.
            $jobRequest->requirements()->delete();

            foreach ($validated['requirements'] ?? [] as $req) {
                $jobRequest->requirements()->create([
                    'technology_id' => $req['technology_id'],
                    'type' => $req['type'],
                    'weight' => $req['weight'],
                ]);
            }
        });

        return to_route('requests.show', $jobRequest)->with('success', 'Запрос обновлён.');
    }

    /**
     * Remove the specified request from storage.
     */
    public function destroy(JobRequest $jobRequest): RedirectResponse
    {
        $this->authorize('delete', $jobRequest);

        $jobRequest->delete();

        return to_route('requests.index')->with('success', 'Запрос удалён.');
    }
}