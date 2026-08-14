<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.activity-log.index', [
            'logs' => ActivityLog::query()
                ->with('user')
                ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
                ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.$request->string('q').'%'))
                ->latest('created_at')
                ->paginate(30)
                ->withQueryString(),
            'actions' => ActivityLogger::actionOptions(),
            'users' => User::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
