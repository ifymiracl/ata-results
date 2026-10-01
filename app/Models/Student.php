<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $guarded = [];
    protected $hidden = ['pin_hash', 'parent_pin_hash'];
    protected $casts = ['must_change_pin' => 'boolean', 'parent_must_change_pin' => 'boolean', 'date_of_birth' => 'date'];

    public function school() { return $this->belongsTo(School::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function results() { return $this->hasMany(Result::class); }
    public function fees() { return $this->hasMany(StudentFee::class); }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
