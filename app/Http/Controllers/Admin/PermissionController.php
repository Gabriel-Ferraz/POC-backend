<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::all();

        return response()->json(PermissionResource::collection($permissions));
    }

    public function show(int $id): PermissionResource
    {
        $permission = Permission::findOrFail($id);

        return new PermissionResource($permission);
    }
}
