<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Candidate;
use App\Models\JobRequest;
use App\Services\MatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function __construct(
        protected MatchService $matchService,
    ) {}

    /**
     * Show form for selecting a candidate + request pair to match.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Assessment::class);

        return Inertia::render('Assessments/Create', [
            'candidates' => Candidate::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'grade']),
            'requests' => JobRequest::query()
                ->where('status', 'open')
                ->orderBy('position')
                ->get(['id', 'position', 'grade']),
            'preselect' => [
                'candidate_id' => $request->integer('candidate_id') ?: null,
                'request_id'   => $request->integer('request_id') ?: null,
            ],
        ]);
    }

    /**
     * Run the match and redirect to the assessment result page.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'request_id'   => ['required', 'integer', 'exists:requests,id'],
        ]);

        $candidate  = Candidate::findOrFail($validated['candidate_id']);
        $jobRequest = JobRequest::findOrFail($validated['request_id']);

        $assessment = $this->matchService->match($candidate, $jobRequest);

        return to_route('assessments.show', $assessment)
            ->with('success', "Сверка выполнена. Покрытие: {$assessment->coverage_percent}%");
    }

    /**
     * Show assessment result.
     */
    public function show(Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'candidate',
            'request.requirements.technology',
            'requirementResults',
            'calculatedBy:id,name',
        ]);

        $requirements = $assessment->request->requirements->map(function ($req) use ($assessment) {
            $result = $assessment->requirementResults
                ->firstWhere('requirement_id', $req->id);

            return [
                'id'         => $req->id,
                'technology' => $req->technology->name,
                'type'       => $req->type,
                'weight'     => $req->weight,
                'is_matched' => $result?->is_matched ?? false,
            ];
        });

        return Inertia::render('Assessments/Show', [
            'assessment' => [
                'id'               => $assessment->id,
                'coverage_percent' => $assessment->coverage_percent,
                'calculated_by'    => $assessment->calculatedBy?->name,
                'updated_at'       => $assessment->updated_at->format('d.m.Y H:i'),
                'candidate'        => [
                    'id'        => $assessment->candidate->id,
                    'full_name' => $assessment->candidate->full_name,
                    'grade'     => $assessment->candidate->grade,
                ],
                'request'          => [
                    'id'       => $assessment->request->id,
                    'position' => $assessment->request->position,
                    'grade'    => $assessment->request->grade,
                ],
                'requirements'     => $requirements,
            ],
        ]);
    }

    /**
     * Download assessment as PDF report.
     */
    public function pdf(Assessment $assessment): HttpResponse
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'candidate',
            'request.requirements.technology',
            'requirementResults',
            'calculatedBy:id,name',
        ]);

        $requirements = $assessment->request->requirements->map(function ($req) use ($assessment) {
            $result = $assessment->requirementResults->firstWhere('requirement_id', $req->id);

            return [
                'technology' => $req->technology->name,
                'type'       => $req->type,
                'weight'     => $req->weight,
                'is_matched' => $result?->is_matched ?? false,
            ];
        });

        $mustReqs    = $requirements->where('type', 'must');
        $niceReqs    = $requirements->where('type', 'nice');
        $mustMatched = $mustReqs->where('is_matched', true)->count();
        $niceMatched = $niceReqs->where('is_matched', true)->count();

        $coverageColor = match (true) {
            $assessment->coverage_percent >= 80 => '#15803d',
            $assessment->coverage_percent >= 50 => '#b45309',
            default                             => '#b91c1c',
        };

        $gradeLabels = [
            'junior' => 'Junior',
            'middle' => 'Middle',
            'senior' => 'Senior',
            'lead'   => 'Lead',
        ];

        $pdf = Pdf::loadView('pdf.assessment', [
            'assessment'    => $assessment,
            'requirements'  => $requirements,
            'mustMatched'   => $mustMatched,
            'mustTotal'     => $mustReqs->count(),
            'niceMatched'   => $niceMatched,
            'niceTotal'     => $niceReqs->count(),
            'coverageColor' => $coverageColor,
            'gradeLabels'   => $gradeLabels,
        ]);

        $filename = sprintf(
            'assessment-%s-%s.pdf',
            str($assessment->candidate->full_name)->slug(),
            str($assessment->request->position)->slug(),
        );

        return $pdf->download($filename);
    }
}