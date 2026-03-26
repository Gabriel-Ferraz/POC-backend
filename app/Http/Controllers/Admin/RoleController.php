<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Trait\Auditable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\{StoreRoleRequest, SyncPermissionsRequest, UpdateRoleRequest};
use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    use Auditable;

    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json(RoleResource::collection($roles));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $this->audit('store', 'Role', $role->id, $request->validated());

        return (new RoleResource($role->load('permissions')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): RoleResource
    {
        $role = Role::with('permissions')->findOrFail($id);

        return new RoleResource($role);
    }

    public function update(UpdateRoleRequest $request, int $id): RoleResource|JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return response()->json([
                'message' => 'Cannot modify the super-admin role',
            ], Response::HTTP_FORBIDDEN);
        }

        $role->update(['name' => $request->validated('name')]);

        $this->audit('update', 'Role', $role->id, $request->validated());

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(int $id): Response
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return response()->json([
                'message' => 'Cannot delete the super-admin role',
            ], Response::HTTP_FORBIDDEN);
        }

        $role->delete();

        $this->audit('destroy', 'Role', $role->id, ['name' => $role->name]);

        return response()->noContent();
    }

    public function syncPermissions(SyncPermissionsRequest $request, int $id): RoleResource|JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return response()->json([
                'message' => 'Cannot modify permissions of the super-admin role',
            ], Response::HTTP_FORBIDDEN);
        }

        $role->syncPermissions($request->validated('permissions'));

        $this->audit('sync_permissions', 'Role', $role->id, $request->validated());

        return new RoleResource($role->load('permissions'));
    }
}
