<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Requests\UploadResumeRequest;
use App\Models\Candidate;
use App\Models\Technology;
use App\Services\ResumeParserService;
use App\Services\SkillDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function __construct(
        protected ResumeParserService $parser,
        protected SkillDetectionService $skillDetector,
    ) {}

    /**
     * Display a listing of candidates, with search by skill/name.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Candidate::class);

        $candidates = Candidate::query()
            ->with('skills:id,name')
            ->withCount('skills')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('full_name', 'like', "%{$search}%");
            })
            ->when($request->filled('grade'), fn ($q) => $q->where('grade', $request->input('grade')))
            ->when($request->filled('technology_id'), function ($q) use ($request) {
                $q->whereHas('skills', fn ($sq) => $sq->where('technologies.id', $request->input('technology_id')));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Candidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only(['search', 'grade', 'technology_id']),
            'technologies' => Technology::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the resume upload form.
     */
    public function create(): Response
    {
        $this->authorize('create', Candidate::class);

        return Inertia::render('Candidates/Upload');
    }

    /**
     * Handle resume upload: store file, extract text, detect skills, create/update candidate.
     */
    public function store(UploadResumeRequest $request): RedirectResponse
    {
        $file = $request->file('resume');
        $extension = strtolower($file->getClientOriginalExtension());
        $fullName = $this->guessNameFromFilename($file->getClientOriginalName());

        // Store the file first so we have an absolute path to parse from.
        $storedPath = $file->store('resumes', 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $rawText = $this->parser->extractText($absolutePath, $extension);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);

            return back()->with('error', 'Не удалось обработать файл: '.$e->getMessage());
        }

        $matchedTechnologies = $this->skillDetector->detect($rawText);

        $candidate = DB::transaction(function () use ($request, $fullName, $storedPath, $rawText, $matchedTechnologies) {
            $candidate = Candidate::where('full_name', $fullName)->first();

            $attributes = [
                'full_name' => $fullName,
                'file_path' => $storedPath,
                'raw_text' => $rawText,
                'uploaded_by' => $request->user()->id,
            ];

            // Only overwrite grade/location if explicitly provided; otherwise keep existing values on update.
            if ($request->filled('grade')) {
                $attributes['grade'] = $request->input('grade');
            }
            if ($request->filled('location')) {
                $attributes['location'] = $request->input('location');
            }

            if ($candidate) {
                // Delete the old file before replacing it, to avoid orphaned files accumulating.
                if ($candidate->file_path && $candidate->file_path !== $storedPath) {
                    Storage::disk('local')->delete($candidate->file_path);
                }

                $candidate->update($attributes);
            } else {
                $candidate = Candidate::create($attributes);
            }

            $candidate->skills()->sync($matchedTechnologies->pluck('id'));

            return $candidate;
        });

        $message = $matchedTechnologies->isNotEmpty()
            ? "Резюме обработано. Найдено технологий: {$matchedTechnologies->count()}."
            : 'Резюме обработано, но технологии из справочника не найдены в тексте.';

        return to_route('candidates.show', $candidate)->with('success', $message);
    }

    /**
     * Display the specified candidate.
     */
    public function show(Candidate $candidate): Response
    {
        $this->authorize('view', $candidate);

        $candidate->load(['skills:id,name,group', 'uploader:id,name']);

        return Inertia::render('Candidates/Show', [
            'candidate' => $candidate,
        ]);
    }

    /**
     * Show the form for editing the specified candidate.
     */
    public function edit(Candidate $candidate): Response
    {
        $this->authorize('update', $candidate);

        $candidate->load('skills:id');

        return Inertia::render('Candidates/Edit', [
            'candidate' => [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'grade' => $candidate->grade,
                'location' => $candidate->location,
                'skill_ids' => $candidate->skills->pluck('id'),
            ],
            'technologies' => Technology::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'group']),
        ]);
    }

    /**
     * Update the specified candidate (manual corrections after auto-detection).
     */
    public function update(UpdateCandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $candidate) {
            $candidate->update([
                'full_name' => $validated['full_name'],
                'grade' => $validated['grade'] ?? null,
                'location' => $validated['location'] ?? null,
            ]);

            $candidate->skills()->sync($validated['skills'] ?? []);
        });

        return to_route('candidates.show', $candidate)->with('success', 'Кандидат обновлён.');
    }

    /**
     * Remove the specified candidate from storage.
     */
    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->authorize('delete', $candidate);

        if ($candidate->file_path) {
            Storage::disk('local')->delete($candidate->file_path);
        }

        $candidate->delete();

        return to_route('candidates.index')->with('success', 'Кандидат удалён.');
    }

    /**
     * Guess a candidate's full name from the uploaded filename.
     *
     * Strips the extension, replaces separators (- _ .) with spaces, and
     * title-cases the result. E.g. "john_doe_resume.pdf" -> "John Doe Resume".
     */
    protected function guessNameFromFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['_', '-', '.'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title(trim($name));
    }
}