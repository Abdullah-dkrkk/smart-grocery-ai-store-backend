<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::active()->get(['id', 'text', 'sort_order']);
        return response()->json([
            'success' => true,
            'data' => $announcements,
        ]);
    }
}
