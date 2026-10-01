<?php

namespace App\Services;

use App\Models\AcademicAward;
use App\Models\AnnualResult;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;

class Promotion
{
    /** "JSS 2 Gold" -> "JSS 2": groups arms into a level for ranking and promotion. */
    public static function level(string $class): string
    {
        if (preg_match('/^(JSS\s*\d+|SS\s*\d+|Primary\s*\d+|Nursery\s*\d+)/i', trim($class), $m)) { return preg_replace('/\s+/', ' ', trim($m[1])); }
        return trim($class);
    }

    public static function isTerminal(string $class): bool
    {
        return strtoupper(self::level($class)) === 'SS 3';
    }

    public static function nextLevel(string $level): string
    {
        if (! preg_match('/^(JSS|SS|Primary|Nursery)\s*(\d+)$/i', $level, $m)) { return ''; }
        $maxes = ['nursery' => [2, 'Primary 1'], 'primary' => [6, 'JSS 1'], 'jss' => [3, 'SS 1'], 'ss' => [3, '']];
        [$max, $next] = $maxes[strtolower($m[1])];
        return (int) $m[2] >= $max ? $next : $m[1] . ' ' . ((int) $m[2] + 1);
    }

    public static function compute(School $school, string $session): int
    {
        $benchmark = (float) $school->promotion_benchmark;
        $students = Student::where('school_id', $school->id)->active()->get();
        $avgs = Result::where(['school_id' => $school->id, 'session_label' => $session, 'status' => 'published'])->selectRaw('student_id, AVG(total) a')->groupBy('student_id')->pluck('a', 'student_id');

        $computed = [];
        foreach ($students as $s) {
            if (isset($avgs[$s->id])) { $computed[$s->id] = ['average' => round((float) $avgs[$s->id], 2), 'class' => $s->class_name]; }
        }
        if (! $computed) { return 0; }

        $byClass = []; $byLevel = [];
        foreach ($computed as $id => $c) { $byClass[$c['class']][$id] = $c['average']; $byLevel[self::level($c['class'])][$id] = $c['average']; }
        $rank = function (array $set, int $id): int { arsort($set); $r = 0; $prev = null; $i = 0; foreach ($set as $k => $v) { $i++; if ($v !== $prev) { $r = $i; $prev = $v; } if ($k === $id) { return $r; } } return $i; };

        foreach ($computed as $id => &$c) {
            $level = self::level($c['class']); $terminal = self::isTerminal($c['class']);
            $pass = $c['average'] >= $benchmark;
            $c['class_position'] = $rank($byClass[$c['class']], $id); $c['class_population'] = count($byClass[$c['class']]);
            $c['level_position'] = $rank($byLevel[$level], $id); $c['level_population'] = count($byLevel[$level]);
            $c['action'] = $terminal ? ($pass ? 'graduate' : 'repeat') : ($pass ? 'promote' : 'repeat');
            $c['target'] = $pass ? ($terminal ? '' : self::nextLevel($level)) : $c['class'];
        }
        unset($c);

        // arm streaming: when a target level has 2+ arms with capacities, fill best-first
        $promoted = [];
        foreach ($computed as $id => $c) { if ($c['action'] === 'promote' && $c['target']) { $promoted[$c['target']][$id] = $c['average']; } }
        foreach ($promoted as $level => $set) {
            $arms = SchoolClass::where('school_id', $school->id)->where(fn ($q) => $q->where('name', $level)->orWhere('name', 'like', $level . ' %'))->orderBy('sort_order')->orderBy('name')->get()->values();
            if ($arms->count() < 2 || $arms->whereNotNull('capacity')->isEmpty()) { continue; }
            arsort($set);
            $overflow = $arms->first(fn ($a) => $a->capacity === null)?->name ?? $arms->last()->name;
            $slots = $arms->whereNotNull('capacity')->mapWithKeys(fn ($a) => [$a->name => (int) $a->capacity])->all();
            foreach (array_keys($set) as $id) {
                $assigned = $overflow;
                foreach ($slots as $name => $left) { if ($left > 0) { $assigned = $name; $slots[$name]--; break; } }
                $computed[$id]['target'] = $assigned;
            }
        }

        foreach ($computed as $id => $c) {
            AnnualResult::updateOrCreate(['school_id' => $school->id, 'student_id' => $id, 'session_label' => $session], [
                'annual_average' => $c['average'], 'class_position' => $c['class_position'], 'class_population' => $c['class_population'],
                'level_position' => $c['level_position'], 'level_population' => $c['level_population'],
                'recommended_action' => $c['action'], 'recommended_class' => $c['target'] ?: null, 'applied' => false,
            ]);
        }

        AcademicAward::where(['school_id' => $school->id, 'session_label' => $session])->delete();
        foreach ($byLevel as $level => $set) {
            arsort($set);
            AcademicAward::create(['school_id' => $school->id, 'student_id' => array_key_first($set), 'session_label' => $session, 'award_type' => 'overall_best', 'level_label' => $level]);
        }
        // best in each subject per level
        $best = Result::where(['school_id' => $school->id, 'session_label' => $session, 'status' => 'published'])->selectRaw('student_id, subject, AVG(total) a')->groupBy('student_id', 'subject')->get();
        $levelOf = $students->pluck('class_name', 'id')->map(fn ($c) => self::level($c));
        foreach ($best->groupBy(fn ($r) => ($levelOf[$r->student_id] ?? '?') . '|' . $r->subject) as $key => $grp) {
            [$level, $subject] = explode('|', $key, 2);
            if ($level === '?') { continue; }
            $top = $grp->sortByDesc('a')->first();
            AcademicAward::create(['school_id' => $school->id, 'student_id' => $top->student_id, 'session_label' => $session, 'award_type' => 'subject_best', 'subject' => $subject, 'level_label' => $level]);
        }
        return count($computed);
    }

    /** Actually move the students: promote to target class, graduate, or leave repeaters. Idempotent per row. */
    public static function apply(School $school, string $session): array
    {
        $moved = $graduated = $repeat = 0;
        foreach (AnnualResult::where(['school_id' => $school->id, 'session_label' => $session, 'applied' => false])->get() as $ar) {
            $s = Student::find($ar->student_id);
            if (! $s) { continue; }
            if ($ar->recommended_action === 'promote' && $ar->recommended_class) {
                if (SchoolClass::where('school_id', $school->id)->where('name', $ar->recommended_class)->exists()) { $s->update(['class_name' => $ar->recommended_class]); $moved++; }
                else { $s->update(['status' => 'graduated']); $graduated++; } // school has no higher level
            }
            elseif ($ar->recommended_action === 'graduate') { $s->update(['status' => 'graduated']); $graduated++; }
            else { $repeat++; }
            $ar->update(['applied' => true]);
        }
        return compact('moved', 'graduated', 'repeat');
    }
}
