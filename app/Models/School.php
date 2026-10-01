<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $guarded = [];
    protected $casts = [
        'grading_scale' => 'array', 'score_weights' => 'array',
        'trial_ends_at' => 'datetime', 'subscription_paid_until' => 'datetime',
        'next_term_begins' => 'date', 'paystack_secret_key' => 'encrypted',
    ];
    protected $hidden = ['paystack_secret_key'];

    public const DEFAULT_SCALE = [
        ['min' => 75, 'grade' => 'A1', 'remark' => 'Excellent'],
        ['min' => 70, 'grade' => 'B2', 'remark' => 'Very good'],
        ['min' => 65, 'grade' => 'B3', 'remark' => 'Good'],
        ['min' => 60, 'grade' => 'C4', 'remark' => 'Credit'],
        ['min' => 55, 'grade' => 'C5', 'remark' => 'Credit'],
        ['min' => 50, 'grade' => 'C6', 'remark' => 'Credit'],
        ['min' => 45, 'grade' => 'D7', 'remark' => 'Pass'],
        ['min' => 40, 'grade' => 'E8', 'remark' => 'Pass'],
        ['min' => 0, 'grade' => 'F9', 'remark' => 'Fail'],
    ];

    public function getRouteKeyName(): string { return 'slug'; }

    public function weights(): array
    {
        return array_merge(['ca1' => 15, 'ca2' => 15, 'exam' => 70], $this->score_weights ?? []);
    }

    public function scale(): array
    {
        $s = $this->grading_scale ?: self::DEFAULT_SCALE;
        usort($s, fn ($a, $b) => $b['min'] <=> $a['min']);
        return $s;
    }

    public function gradeFor(float $total): array
    {
        foreach ($this->scale() as $band) {
            if ($total >= (float) $band['min']) { return ['grade' => $band['grade'], 'remark' => $band['remark']]; }
        }
        $scale = $this->scale();
        $last = end($scale);
        return ['grade' => $last['grade'] ?? 'F9', 'remark' => $last['remark'] ?? 'Fail'];
    }

    public function isUnlocked(): bool
    {
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) { return true; }
        if ($this->subscription_paid_until && $this->subscription_paid_until->isFuture()) { return true; }
        return ! $this->trial_ends_at && ! $this->subscription_paid_until;
    }

    public function staff() { return $this->hasMany(Staff::class); }
    public function students() { return $this->hasMany(Student::class); }
    public function classes() { return $this->hasMany(SchoolClass::class)->orderBy('sort_order')->orderBy('name'); }
    public function subjects() { return $this->hasMany(Subject::class)->orderBy('name'); }
}
