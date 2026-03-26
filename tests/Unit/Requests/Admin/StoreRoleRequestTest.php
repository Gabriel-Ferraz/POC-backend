<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreRoleRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

describe('StoreRoleRequest', function () {
    it('authorize returns true', function () {
        expect((new StoreRoleRequest())->authorize())->toBeTrue();
    });

    it('passes with valid name', function () {
        $validator = Validator::make(
            ['name' => 'editor'],
            (new StoreRoleRequest())->rules()
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails when name is missing', function () {
        $validator = Validator::make([], (new StoreRoleRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('fails when name exceeds 255 characters', function () {
        $validator = Validator::make([
            'name' => str_repeat('a', 256),
        ], (new StoreRoleRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('fails when role name already exists', function () {
        Role::firstOrCreate(['name' => 'existing-role', 'guard_name' => 'web']);

        $validator = Validator::make(
            ['name' => 'existing-role'],
            (new StoreRoleRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });
});
