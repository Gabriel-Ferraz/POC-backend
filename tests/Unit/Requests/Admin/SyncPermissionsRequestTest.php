<?php

declare(strict_types=1);

use App\Http\Requests\Admin\SyncPermissionsRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

describe('SyncPermissionsRequest', function () {
    it('authorize returns true', function () {
        expect((new SyncPermissionsRequest())->authorize())->toBeTrue();
    });

    it('passes with existing permission names', function () {
        Permission::firstOrCreate(['name' => 'some.action', 'guard_name' => 'web']);

        $validator = Validator::make(
            ['permissions' => ['some.action']],
            (new SyncPermissionsRequest())->rules()
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails with empty permissions array because required rejects empty collections', function () {
        $validator = Validator::make(
            ['permissions' => []],
            (new SyncPermissionsRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('permissions'))->toBeTrue();
    });

    it('fails when permissions field is missing', function () {
        $validator = Validator::make([], (new SyncPermissionsRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('permissions'))->toBeTrue();
    });

    it('fails when permissions is not an array', function () {
        $validator = Validator::make(
            ['permissions' => 'some.action'],
            (new SyncPermissionsRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('permissions'))->toBeTrue();
    });

    it('fails when a permission name does not exist', function () {
        $validator = Validator::make(
            ['permissions' => ['non.existent.permission']],
            (new SyncPermissionsRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('permissions.0'))->toBeTrue();
    });
});
