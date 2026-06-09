<?php

namespace App\Http\Controllers;

use App\Models\Article;

class PublicArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with('nutritionist:id,name')
            ->where('is_published', true)
            ->latest('published_at')
            ->paginate(12);

        return $this->paginateResponse($articles, 'Articles retrieved');
    }

    public function show($slug)
    {
        $article = Article::with('nutritionist:id,name')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return $this->successResponse($article, 'Article retrieved');
    }
}
