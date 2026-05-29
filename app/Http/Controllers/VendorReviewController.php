<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class VendorReviewController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $query = Review::with(['user', 'product'])
            ->whereHas('product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->latest()->paginate($request->input('per_page', 15));

        return $this->paginateResponse($reviews, 'Reviews retrieved');
    }

    public function reply(Request $request, $id)
    {
        $vendorId = $request->user()->id;

        $validated = $request->validate([
            'reply' => 'required|string',
        ]);

        $review = Review::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->findOrFail($id);

        $review->update([
            'vendor_reply' => $validated['reply'],
            'vendor_replied_at' => now(),
        ]);

        return $this->successResponse($review, 'Reply posted');
    }
}
