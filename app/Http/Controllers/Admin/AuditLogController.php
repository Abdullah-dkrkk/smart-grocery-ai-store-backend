<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->latest('created_at')->paginate($request->input('per_page', 15));

        return $this->paginateResponse($logs, 'Audit logs retrieved');
    }
}
