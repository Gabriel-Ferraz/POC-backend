<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;

describe('LoginRequest', function () {
    it('authorize returns true', function () {
        expect((new LoginRequest())->authorize())->toBeTrue();
    });

    it('passes with valid credentials', function () {
        $validator = Validator::make([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ], (new LoginRequest())->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails when email is missing', function () {
        $validator = Validator::make([
            'password' => 'secret123',
        ], (new LoginRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when email format is invalid', function () {
        $validator = Validator::make([
            'email' => 'not-an-email',
            'password' => 'secret123',
        ], (new LoginRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    it('fails when password is missing', function () {
        $validator = Validator::make([
            'email' => 'user@example.com',
        ], (new LoginRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    it('fails when password is shorter than 6 characters', function () {
        $validator = Validator::make([
            'email' => 'user@example.com',
            'password' => '123',
        ], (new LoginRequest())->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });
});
