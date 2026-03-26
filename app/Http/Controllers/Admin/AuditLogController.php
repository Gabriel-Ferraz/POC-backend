<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::with('user')
            ->filterData($request->all())
            ->orderByDesc('created_at')
            ->paginate(
                $request->query('perPage', 20),
                ['*'],
                'page',
                $request->query('page', 1),
            );

        return AuditLogResource::collection($logs);
    }

    public function show(int $id): AuditLogResource
    {
        $log = AuditLog::with('user')->findOrFail($id);

        return new AuditLogResource($log);
    }
}
