<?php

namespace App\Http\Controllers;

use App\Models\Headline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeadlineController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'nullable',
        ]);
        $categories = collect(explode(',', $validated['categories'] ?? ''))
            ->filter(fn($category) => in_array($category, Headline::CATEGORIES));

        $headlines = Headline::query()
            ->when($categories->isNotEmpty(), fn ($query) => $query->whereIn('category', $categories))
            ->with('forwardRefs', 'backwardRefs')
            ->orderBy('id')
            ->get();

        return response()->json([
            'headlines' => $headlines
        ]);
    }

    public function show(Headline $headline)
    {
        return response()->json([
            'headline' => $headline->load('forwardRefs', 'backwardRefs')
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:'.implode(',', Headline::CATEGORIES),
            'title' => 'required|max:100',
            'description' => 'nullable',
            'forward_ref_ids' => 'nullable|array',
            'backward_ref_ids' => 'nullable|array',
        ]);

        $headline = DB::transaction(function () use ($validated) {
            /** @var Headline $headline */
            $headline = Headline::create($validated)->load('forwardRefs', 'backwardRefs');
            if (isset($validated['forward_ref_ids'])) {
                $this->updateForwardRefs($headline, $validated['forward_ref_ids']);
            }

            return $headline;
        });

        return response()->json([
            'headline' => $headline->load('forwardRefs', 'backwardRefs')
        ], 201);
    }

    /**
     * @param Request $request
     * @param Headline $headline
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function update(Request $request, Headline $headline)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:'.implode(',', Headline::CATEGORIES),
            'title' => 'required|max:100',
            'description' => 'nullable',
            'forward_ref_ids' => 'nullable|array',
        ]);

        $headline = DB::transaction(function () use ($headline, $validated) {
            $headline->fill($validated)->save();
            if (isset($validated['forward_ref_ids'])) {
                $this->updateForwardRefs($headline, $validated['forward_ref_ids']);
            }

            return $headline;
        });

        return response()->json([
            'headline' => $headline->load('forwardRefs', 'backwardRefs')
        ]);
    }

    public function destroy(Headline $headline)
    {
        $headline->forwardRefs()->detach();
        $headline->backwardRefs()->detach();
        $headline->delete();
    }

    private function updateForwardRefs(Headline $headline, array $requestForwardRefIds): void
    {
        $backwardRefIds = $headline->backwardRefs()->pluck('id');
        $newRefIds = collect($requestForwardRefIds)
            ->mapWithKeys(fn ($refId) => [$refId => $refId])
            ->except($backwardRefIds)
            ->unique();

        $headline->forwardRefs()->sync($newRefIds);
    }
}
