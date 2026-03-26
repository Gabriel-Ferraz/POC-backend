<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

describe('StoreUserRequest', function () {
    it('authorize returns true', function () {
        expect((new StoreUserRequest())->authorize())->toBeTrue();
    });

    it('passes with valid data', function () {
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when name is missing', function () {
        $validator = Validator::make([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('fails when email is missing', function () {
        $validator = Validator::make([
            'name' => 'John',
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when email is invalid format', function () {
        $validator = Validator::make([
            'name' => 'John',
            'email' => 'not-an-email',
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when email is already taken', function () {
        $existing = User::factory()->create();

        $validator = Validator::make([
            'name' => 'John',
            'email' => $existing->email,
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when password is missing', function () {
        $validator = Validator::make([
            'name' => 'John',
            'email' => 'john@example.com',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    it('fails when password is shorter than 6 characters', function () {
        $validator = Validator::make([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => '123',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    it('passes when is_active is omitted', function () {
        $validator = Validator::make([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ], (new StoreUserRequest())->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when is_active is not boolean', function () {
        $validator = Validator::make([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'is_active' => 'yes',
        ], (new StoreUserRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('is_active'))->toBeTrue();
    });
});
