<?php

namespace App\Http\Middleware;

use App\Services\Portal;
use Closure;
use Illuminate\Http\Request;

/** Usage: portal  |  portal:staff  |  portal:staff,school_admin,principal  (type, then optional staff roles) */
class PortalAuth
{
    public function handle(Request $request, Closure $next, ...$rules)
    {
        $school = $request->route('school');
        $me = Portal::current($school);
        if (! $me) {
            return redirect()->route('login', $school)->with('err', 'Please sign in to continue.');
        }
        if (Portal::mustChangePin($me) && ! $request->routeIs('pin.*', 'logout')) {
            return redirect()->route('pin.form', $school);
        }
        if ($rules) {
            $type = array_shift($rules);
            if ($type === 'staff') {
                if ($me['type'] !== 'staff' || ($rules && ! in_array($me['model']->role, $rules, true))) { abort(403, 'Not allowed.'); }
            } elseif ($type === 'family') {
                if (! in_array($me['type'], ['student', 'parent'], true)) { abort(403, 'Not allowed.'); }
            }
        }
        $request->attributes->set('me', $me);
        view()->share('me', $me);
        return $next($request);
    }
}
