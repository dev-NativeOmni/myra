<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display the audit trail of report data changes.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('created_at')->latest('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('student_name', 'like', "%{$search}%");
        }

        $logs = $query->paginate(25)->withQueryString();
        $staff = User::where('role', '!=', User::ROLE_WALI_MURID)->orderBy('name')->get(['id', 'name']);

        return view('audit_logs.index', compact('logs', 'staff'));
    }
}
