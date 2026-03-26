<?php

declare(strict_types=1);

use App\Models\{AuditLog, User};
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

it('returns 401 on audit-logs index when unauthenticated', function () {
    getJson('/api/admin/audit-logs')->assertUnauthorized();
});

describe('index', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->actor = User::factory()->create();
        $this->actor->assignRole('super-admin');
        Sanctum::actingAs($this->actor);
    });

    it('returns paginated audit logs', function () {
        AuditLog::factory()->count(3)->create(['user_id' => $this->actor->id]);

        getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    });

    it('filters by user_id', function () {
        $other = User::factory()->create();
        AuditLog::factory()->create(['user_id' => $this->actor->id, 'action' => 'login']);
        AuditLog::factory()->create(['user_id' => $other->id, 'action' => 'logout']);

        $response = getJson("/api/admin/audit-logs?user_id={$this->actor->id}")->assertOk();

        collect($response->json('data'))->each(
            fn ($item) => expect($item['user_id'])->toBe($this->actor->id)
        );
    });

    it('filters by action', function () {
        AuditLog::factory()->create(['action' => 'store']);
        AuditLog::factory()->create(['action' => 'destroy']);

        $response = getJson('/api/admin/audit-logs?action=store')->assertOk();

        collect($response->json('data'))->each(
            fn ($item) => expect($item['action'])->toBe('store')
        );
    });

    it('filters by entity', function () {
        AuditLog::factory()->create(['entity' => 'User']);
        AuditLog::factory()->create(['entity' => 'Role']);

        $response = getJson('/api/admin/audit-logs?entity=User')->assertOk();

        collect($response->json('data'))->each(
            fn ($item) => expect($item['entity'])->toBe('User')
        );
    });

    it('filters by date range', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(5)]);
        AuditLog::factory()->create(['created_at' => now()->subDay()]);

        $from = now()->subDays(3)->toDateString();

        getJson("/api/admin/audit-logs?date_from={$from}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('show', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->actor = User::factory()->create();
        $this->actor->assignRole('super-admin');
        Sanctum::actingAs($this->actor);
    });

    it('returns audit log with user', function () {
        $log = AuditLog::factory()->create(['user_id' => $this->actor->id]);

        getJson("/api/admin/audit-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.user_name', $this->actor->name);
    });

    it('returns 404 for non-existent log', function () {
        getJson('/api/admin/audit-logs/99999')->assertNotFound();
    });
});
