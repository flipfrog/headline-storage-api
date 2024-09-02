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
            'forwardRefs' => 'nullable|array',
            'backwardRefs' => 'nullable|array',
        ]);

        /** @var Headline $headline */
        $headline = Headline::create($validated)->load('forwardRefs', 'backwardRefs');

        if (!empty($validated['forwardRefs'])) {
            $headline->forwardRefs()->sync($validated['forwardRefs']);
        }
        if (!empty($validated['backwardRefs'])) {
            $headline->backwardRefs()->sync($validated['backwardRefs']);
        }
        if (!empty($validated['forwardRefs']) || !empty($validated['backwardRefs'])) {
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
            'forwardRefs' => 'nullable|array',
            'backwardRefs' => 'nullable|array',
        ]);

        // check duplication for refs.
        $refIds = $headline->forwardRefs->merge($headline->backwardRefs)->pluck('id');
        if ($refIds->intersect(collect($validated['forwardRefs'] ?? []))->isNotEmpty()) {
            return response()->json(['forwardRefs' => ['forwardRefs is duplicated.']], 409);
        }
        if ($refIds->intersect(collect($validated['backwardRefs']?? []))->isNotEmpty()) {
            return response()->json(['backwardRefs' => ['backwardRefs is duplicated.']], 409);
        }

        $headline->fill($validated)->save();

        if (isset($validated['forwardRefs'])) {
            $headline->forwardRefs()->sync($validated['forwardRefs']);
        }
        if (isset($validated['backwardRefs'])) {
            $headline->backwardRefs()->sync($validated['backwardRefs']);
        }
        if (isset($validated['forwardRefs']) || isset($validated['backwardRefs'])) {
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
