<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class NutritionistArticleController extends Controller
{
    public function index(Request $request)
    {
        $nutritionistId = $request->user()->id;

        $articles = Article::where('nutritionist_id', $nutritionistId)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->paginateResponse($articles, 'Articles retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string|url',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'is_published' => 'boolean',
        ]);

        $article = Article::create([
            'nutritionist_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $validated['image_url'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'published_at' => !empty($validated['is_published']) ? now() : null,
        ]);

        return $this->successResponse($article, 'Article created', 201);
    }

    public function show(Request $request, $id)
    {
        $article = Article::where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse($article, 'Article retrieved');
    }

    public function update(Request $request, $id)
    {
        $article = Article::where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|string|url',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'is_published' => 'boolean',
        ]);

        if (isset($validated['is_published']) && $validated['is_published'] && !$article->published_at) {
            $validated['published_at'] = now();
        }

        $article->update($validated);

        return $this->successResponse($article, 'Article updated');
    }

    public function destroy(Request $request, $id)
    {
        $article = Article::where('nutritionist_id', $request->user()->id)
            ->findOrFail($id);

        $article->delete();

        return $this->successResponse(null, 'Article deleted');
    }
}
