<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

describe('UpdateUserRequest', function () {
    it('authorize returns true', function () {
        expect((new UpdateUserRequest())->authorize())->toBeTrue();
    });

    it('passes with empty data (all fields are sometimes)', function () {
        $request = new UpdateUserRequest();

        $validator = Validator::make([], $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes with valid partial data', function () {
        $validator = Validator::make([
            'name' => 'Updated Name',
        ], (new UpdateUserRequest())->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when email format is invalid', function () {
        $validator = Validator::make([
            'email' => 'not-valid',
        ], (new UpdateUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when email is taken by another user', function () {
        $other = User::factory()->create();

        $validator = Validator::make([
            'email' => $other->email,
        ], (new UpdateUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when password is shorter than 6 characters', function () {
        $validator = Validator::make([
            'password' => '123',
        ], (new UpdateUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    it('fails when is_active is not boolean', function () {
        $validator = Validator::make([
            'is_active' => 'maybe',
        ], (new UpdateUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('is_active'))->toBeTrue();
    });
});
