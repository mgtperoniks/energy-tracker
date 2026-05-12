<?php

namespace App\Http\Controllers;

use App\Models\TaggingAuditLog;
use Illuminate\Http\Request;

class TaggingAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = TaggingAuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('tag_type')) {
            $query->where('tag_type', $request->tag_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->orderByDesc('event_at')->paginate(25);
        $users = \App\Models\User::all();

        return view('admin.tagging_audit', compact('logs', 'users'));
    }
}
