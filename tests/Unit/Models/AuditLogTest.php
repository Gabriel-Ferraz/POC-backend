<?php

declare(strict_types=1);

use App\Models\{AuditLog, User};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

describe('AuditLog model', function () {
    it('has correct fillable attributes', function () {
        $log = new AuditLog();

        expect($log->getFillable())->toBe([
            'user_id',
            'action',
            'entity',
            'entity_id',
            'payload',
            'ip',
            'user_agent',
        ]);
    });

    it('disables automatic timestamps', function () {
        expect((new AuditLog())->timestamps)->toBeFalse();
    });

    it('casts payload as array', function () {
        $log = new AuditLog();

        expect($log->getCasts()['payload'])->toBe('array');
    });

    it('casts created_at as datetime', function () {
        $log = new AuditLog();

        expect($log->getCasts()['created_at'])->toBe('datetime');
    });

    it('user() is a BelongsTo relationship', function () {
        $log = new AuditLog();

        expect($log->user())->toBeInstanceOf(BelongsTo::class);
    });

    it('scopeByUser filters by user_id', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        AuditLog::factory()->create(['user_id' => $userA->id]);
        AuditLog::factory()->create(['user_id' => $userB->id]);

        $results = AuditLog::byUser($userA->id)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->user_id)->toBe($userA->id);
    });

    it('scopeByAction filters by action', function () {
        AuditLog::factory()->create(['action' => 'login']);
        AuditLog::factory()->create(['action' => 'logout']);

        $results = AuditLog::byAction('login')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->action)->toBe('login');
    });

    it('scopeByEntity filters by entity', function () {
        AuditLog::factory()->create(['entity' => 'User']);
        AuditLog::factory()->create(['entity' => 'Role']);

        $results = AuditLog::byEntity('User')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->entity)->toBe('User');
    });

    it('scopeByDateRange filters from a date', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);
        AuditLog::factory()->create(['created_at' => now()->subDay()]);

        $results = AuditLog::byDateRange(now()->subDays(5)->toDateString(), null)->get();

        expect($results)->toHaveCount(1);
    });

    it('scopeByDateRange filters up to a date', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);
        AuditLog::factory()->create(['created_at' => now()->subDay()]);

        $results = AuditLog::byDateRange(null, now()->subDays(5)->toDateString())->get();

        expect($results)->toHaveCount(1);
    });

    it('scopeByDateRange returns all when both dates are null', function () {
        AuditLog::factory()->count(2)->create();

        $results = AuditLog::byDateRange(null, null)->get();

        expect($results)->toHaveCount(2);
    });

    it('scopeFilterData applies user_id filter', function () {
        $user = User::factory()->create();
        AuditLog::factory()->create(['user_id' => $user->id]);
        AuditLog::factory()->create();

        $results = AuditLog::filterData(['user_id' => $user->id])->get();

        expect($results)->toHaveCount(1);
    });

    it('scopeFilterData applies action filter', function () {
        AuditLog::factory()->create(['action' => 'store']);
        AuditLog::factory()->create(['action' => 'destroy']);

        $results = AuditLog::filterData(['action' => 'store'])->get();

        expect($results)->toHaveCount(1);
    });

    it('scopeFilterData applies entity filter', function () {
        AuditLog::factory()->create(['entity' => 'User']);
        AuditLog::factory()->create(['entity' => 'Role']);

        $results = AuditLog::filterData(['entity' => 'User'])->get();

        expect($results)->toHaveCount(1);
    });

    it('scopeFilterData returns all when no filters provided', function () {
        AuditLog::factory()->count(3)->create();

        $results = AuditLog::filterData([])->get();

        expect($results)->toHaveCount(3);
    });
});
