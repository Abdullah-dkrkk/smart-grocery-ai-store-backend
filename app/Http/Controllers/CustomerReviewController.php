<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->paginateResponse($reviews, 'Reviews retrieved');
    }
}
