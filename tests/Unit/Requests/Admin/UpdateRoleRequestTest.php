<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

describe('UpdateRoleRequest', function () {
    it('authorize returns true', function () {
        expect((new UpdateRoleRequest())->authorize())->toBeTrue();
    });

    it('passes with a unique role name', function () {
        $validator = Validator::make(
            ['name' => 'brand-new-role'],
            (new UpdateRoleRequest())->rules()
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails when name is missing', function () {
        $validator = Validator::make([], (new UpdateRoleRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('fails when name already exists in another role', function () {
        Role::firstOrCreate(['name' => 'taken-name', 'guard_name' => 'web']);

        $validator = Validator::make(
            ['name' => 'taken-name'],
            (new UpdateRoleRequest())->rules()
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('fails when name exceeds 255 characters', function () {
        $validator = Validator::make([
            'name' => str_repeat('x', 256),
        ], (new UpdateRoleRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });
});
