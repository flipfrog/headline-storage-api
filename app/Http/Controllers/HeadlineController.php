<?php

namespace App\Http\Controllers;

use App\Models\Headline;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:'.implode(',', Headline::CATEGORIES),
            'title' => 'required|max:100',
            'description' => 'nullable',
            'forward_ref_ids' => 'nullable|array',
            'backward_ref_ids' => 'nullable|array',
        ]);

        /** @var Headline $headline */
        $headline = Headline::create($validated)->load('forwardRefs', 'backwardRefs');

        if (!empty($validated['forward_ref_ids'])) {
            $headline->forwardRefs()->sync($validated['forward_ref_ids']);
        }
        if (!empty($validated['backward_ref_ids'])) {
            $headline->backwardRefs()->sync($validated['backward_ref_ids']);
        }
        if (!empty($validated['forward_ref_ids']) || !empty($validated['backward_ref_ids'])) {
            $headline->load('forwardRefs', 'backwardRefs');
        }

        return response()->json([
            'headline' => $headline
        ], 201);
    }

    public function update(Request $request, Headline $headline)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:'.implode(',', Headline::CATEGORIES),
            'title' => 'required|max:100',
            'description' => 'nullable',
            'forward_ref_ids' => 'nullable|array',
            'backward_ref_ids' => 'nullable|array',
        ]);

        // check duplication for refs.
        $refIds = $headline->forwardRefs->merge($headline->backwardRefs)->pluck('id');
        if ($refIds->intersect(collect($validated['forward_ref_ids'] ?? []))->isNotEmpty()) {
            return response()->json(['forward_ref_ids' => ['forward_ref_ids is duplicated.']], 409);
        }
        if ($refIds->intersect(collect($validated['backward_ref_ids']?? []))->isNotEmpty()) {
            return response()->json(['backward_ref_ids' => ['backward_ref_ids is duplicated.']], 409);
        }

        $headline->fill($validated)->save();

        if (isset($validated['forward_ref_ids'])) {
            $headline->forwardRefs()->sync($validated['forward_ref_ids']);
        }
        if (isset($validated['backward_ref_ids'])) {
            $headline->backwardRefs()->sync($validated['backward_ref_ids']);
        }
        if (isset($validated['forward_ref_ids']) || isset($validated['backward_ref_ids'])) {
            $headline->load('forwardRefs', 'backwardRefs');
        }

        return response()->json([
            'headline' => $headline
        ]);
    }

    public function destroy(Headline $headline)
    {
        $headline->forwardRefs()->detach();
        $headline->backwardRefs()->detach();
        $headline->delete();
    }
}
