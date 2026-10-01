<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SlugHistory;
use Closure;
use Illuminate\Http\Request;

class ResolveSchool
{
    public function handle(Request $request, Closure $next)
    {
        $slug = (string) $request->route('school');
        $school = School::where('slug', $slug)->where('status', 'active')->first();
        if (! $school) {
            $old = SlugHistory::where('old_slug', $slug)->latest('id')->first();
            if ($old && ($renamed = School::find($old->school_id))) {
                return redirect(str_replace('/' . $slug, '/' . $renamed->slug, $request->fullUrl()), 301);
            }
            abort(404);
        }
        $request->route()->setParameter('school', $school);
        app()->instance('school', $school);
        view()->share('school', $school);
        return $next($request);
    }
}
