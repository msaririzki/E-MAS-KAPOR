<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Stats calculation
        $stats = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', now())->count(),
            'failed' => AuditLog::where('status', 'failed')->count(),
            'auth' => AuditLog::where('category', 'Autentikasi')->count(),
        ];

        // Filters
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereHas('roles', function ($rq) use ($request) {
                        $rq->where('name', $request->role);
                    }
                    );
                });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('details', 'LIKE', "%{$search}%")
                    ->orWhere('action', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('username', 'LIKE', "%{$search}%");
                }
                );
            });
        }

        $perPage = $request->get('per_page', 10);
        $logs = $query->paginate($perPage)->withQueryString();

        $roles = Role::all();
        $categories = AuditLog::select('category')->distinct()->pluck('category');
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.audit_logs.index', compact('logs', 'roles', 'categories', 'actions', 'perPage', 'stats'));
    }
}
