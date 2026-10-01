<?php

namespace App\Services;

use App\Models\AuditLog;

class Audit
{
    public static function log(?int $schoolId, string $actor, string $action, ?string $detail = null): void
    {
        AuditLog::create(['school_id' => $schoolId, 'actor' => $actor, 'action' => $action, 'detail' => $detail, 'ip' => request()->ip()]);
    }

    public static function by(?array $me, string $action, ?string $detail = null): void
    {
        if (! $me) { return; }
        $m = $me['model'];
        $name = $me['type'] === 'staff' ? $m->name . ' (' . $m->role . ')' : $m->full_name . ' (' . $me['type'] . ')';
        self::log($m->school_id, $name, $action, $detail);
    }
}
