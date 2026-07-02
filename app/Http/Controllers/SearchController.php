<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\JobRequest;
use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global search across candidates, job requests and technologies.
     * Returns JSON — consumed by the live search dropdown in the nav.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'candidates'   => [],
                'requests'     => [],
                'technologies' => [],
            ]);
        }

        $candidates = Candidate::query()
            ->where('full_name', 'ilike', "%{$query}%")
            ->limit(5)
            ->get(['id', 'full_name', 'grade'])
            ->map(fn ($c) => [
                'id'       => $c->id,
                'label'    => $c->full_name,
                'sublabel' => $c->grade ?? null,
                'url'      => route('candidates.show', $c->id),
            ]);

        $requests = JobRequest::query()
            ->where('position', 'ilike', "%{$query}%")
            ->limit(5)
            ->get(['id', 'position', 'grade', 'status'])
            ->map(fn ($r) => [
                'id'       => $r->id,
                'label'    => $r->position,
                'sublabel' => $r->status,
                'url'      => route('requests.show', $r->id),
            ]);

        $technologies = Technology::query()
            ->where('name', 'ilike', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'group'])
            ->map(fn ($t) => [
                'id'       => $t->id,
                'label'    => $t->name,
                'sublabel' => $t->group ?? null,
                'url'      => route('admin.technologies.index'),
            ]);

        return response()->json([
            'candidates'   => $candidates,
            'requests'     => $requests,
            'technologies' => $technologies,
        ]);
    }
}