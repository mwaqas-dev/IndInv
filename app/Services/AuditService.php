<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $action, string $module, string $description, ?Model $subject = null, array $old = [], array $new = [], string $result = 'SUCCESS', array $metadata = []): ActivityLog
    {
        $request = app()->bound('request') ? request() : null;
        $user = $request?->user();

        return ActivityLog::create(['user_id' => $user?->id, 'user_name' => $user?->name, 'action' => $action, 'module' => $module, 'subject_type' => $subject ? $subject::class : null, 'subject_id' => $subject?->getKey(), 'subject_label' => $subject?->getAttribute('code') ?? $subject?->getAttribute('number') ?? $subject?->getAttribute('name'), 'description' => $description, 'old_values' => $old ?: null, 'new_values' => $new ?: null, 'result' => $result, 'ip_address' => $request?->ip(), 'user_agent' => substr((string) $request?->userAgent(), 0, 1000), 'url' => $request?->fullUrl(), 'method' => $request?->method(), 'metadata' => $metadata ?: null]);
    }
}
