<?php

declare(strict_types=1);

use App\Http\Requests\Admin\SyncRolesRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

describe('SyncRolesRequest', function () {
    it('authorize returns true', function () {
        expect((new SyncRolesRequest())->authorize())->toBeTrue();
    });

    it('passes with existing role names', function () {
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $validator = Validator::make(
            ['roles' => ['editor']],
            (new SyncRolesRequest())->rules()
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails with empty roles array because required rejects empty collections', function () {
        $validator = Validator::make(
            ['roles' => []],
            (new SyncRolesRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('roles'))->toBeTrue();
    });

    it('fails when roles field is missing', function () {
        $validator = Validator::make([], (new SyncRolesRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('roles'))->toBeTrue();
    });

    it('fails when roles is not an array', function () {
        $validator = Validator::make(
            ['roles' => 'editor'],
            (new SyncRolesRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('roles'))->toBeTrue();
    });

    it('fails when a role name does not exist', function () {
        $validator = Validator::make(
            ['roles' => ['non-existent-role']],
            (new SyncRolesRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('roles.0'))->toBeTrue();
    });
});
