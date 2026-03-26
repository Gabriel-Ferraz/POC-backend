<?php

namespace App\Helpers\Trait;

use App\Models\AuditLog;

trait Auditable
{
    protected function audit(string $action, string $entity, ?int $entityId = null, ?array $payload = null, ?int $userId = null): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'user_id' => $userId ?? $request->user()?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'payload' => $payload,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
