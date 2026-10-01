<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function me(): array { return request()->attributes->get('me'); }

    protected function staff() { return $this->me()['model']; }

    protected function school(): School { return app('school'); }

    protected function isAdmin(): bool { return $this->me()['type'] === 'staff' && $this->staff()->role === 'school_admin'; }

    /** The class/session/term triple most staff screens work with. */
    protected function period(Request $r): array
    {
        $s = $this->school();
        return [
            $r->input('class', ''), $r->input('session', $s->current_session), $r->input('term', $s->current_term),
        ];
    }

    protected function terms(): array { return ['First Term', 'Second Term', 'Third Term']; }

    /** Classes this staff member may see: all for admin/principal, assigned ones for teachers. */
    protected function classesFor($staff): array
    {
        $school = $this->school();
        if (in_array($staff->role, ['school_admin', 'principal'], true)) { return $school->classes->pluck('name')->all(); }
        $names = $staff->assignments()->pluck('class_name')->all();
        if ($staff->assigned_class) { $names[] = $staff->assigned_class; }
        return array_values(array_unique($names));
    }
}
