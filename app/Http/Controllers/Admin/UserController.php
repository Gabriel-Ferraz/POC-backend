<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Trait\Auditable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\{StoreUserRequest, SyncPermissionsRequest, SyncRolesRequest, UpdateUserRequest};
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use Auditable;

    public function index(): AnonymousResourceCollection
    {
        $users = User::with('roles', 'permissions')
            ->paginate(15);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $this->audit('store', 'User', $user->id, $request->safe()->except('password'));

        return (new UserResource($user->load('roles', 'permissions')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): UserResource
    {
        $user = User::with('roles', 'permissions')->findOrFail($id);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = User::findOrFail($id);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        $this->audit('update', 'User', $user->id, $request->safe()->except('password'));

        return new UserResource($user->load('roles', 'permissions'));
    }

    public function destroy(int $id): Response
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super-admin')) {
            return response()->json([
                'message' => 'Cannot delete a super-admin user',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        $this->audit('destroy', 'User', $user->id, ['name' => $user->name, 'email' => $user->email]);

        return response()->noContent();
    }

    public function syncRoles(SyncRolesRequest $request, int $id): UserResource|JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super-admin') && ! in_array('super-admin', $request->validated('roles'))) {
            return response()->json([
                'message' => 'Cannot remove super-admin role from this user',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->syncRoles($request->validated('roles'));

        $this->audit('sync_roles', 'User', $user->id, $request->validated());

        return new UserResource($user->load('roles', 'permissions'));
    }

    public function syncPermissions(SyncPermissionsRequest $request, int $id): UserResource
    {
        $user = User::findOrFail($id);

        $user->syncPermissions($request->validated('permissions'));

        $this->audit('sync_permissions', 'User', $user->id, $request->validated());

        return new UserResource($user->load('roles', 'permissions'));
    }
}
