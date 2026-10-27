<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function record(string $action, $subject, array $changes = []): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject_type' => class_basename($subject), 'subject_id' => $subject->id, 'changes' => $changes]);
    }
}
