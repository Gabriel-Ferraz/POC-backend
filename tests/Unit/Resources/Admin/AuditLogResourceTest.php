<?php

declare(strict_types=1);

use App\Http\Resources\Admin\AuditLogResource;
use App\Models\{AuditLog, User};
use Illuminate\Http\Request;

describe('AuditLogResource', function () {
    it('returns all expected keys', function () {
        $log = AuditLog::factory()->create();

        $array = (new AuditLogResource($log))->toArray(new Request());

        expect($array)->toHaveKeys([
            'id',
            'user_id',
            'user_name',
            'action',
            'entity',
            'entity_id',
            'payload',
            'ip',
            'user_agent',
            'created_at',
        ]);
    });

    it('maps user_name from related user', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $log = AuditLog::factory()->create(['user_id' => $user->id]);
        $log->load('user');

        $array = (new AuditLogResource($log))->toArray(new Request());

        expect($array['user_name'])->toBe('John Doe');
    });

    it('user_name is null when user is not loaded', function () {
        $log = AuditLog::factory()->create(['user_id' => null]);

        $array = (new AuditLogResource($log))->toArray(new Request());

        expect($array['user_name'])->toBeNull();
    });

    it('maps action correctly', function () {
        $log = AuditLog::factory()->create(['action' => 'login']);

        $array = (new AuditLogResource($log))->toArray(new Request());

        expect($array['action'])->toBe('login');
    });

    it('maps entity and entity_id correctly', function () {
        $log = AuditLog::factory()->create(['entity' => 'User', 'entity_id' => 42]);

        $array = (new AuditLogResource($log))->toArray(new Request());

        expect($array['entity'])->toBe('User')
            ->and($array['entity_id'])->toBe(42);
    });
});
