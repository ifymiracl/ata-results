<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';
    protected $guarded = [];
    protected $hidden = ['pin_hash'];
    protected $casts = ['must_change_pin' => 'boolean'];

    public const ROLES = [
        'subject_teacher' => 'Subject teacher', 'form_teacher' => 'Form teacher',
        'principal' => 'Principal', 'school_admin' => 'School admin',
    ];

    public function school() { return $this->belongsTo(School::class); }
    public function assignments() { return $this->hasMany(StaffAssignment::class); }
    public function isAdmin(): bool { return $this->role === 'school_admin'; }
    public function roleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }
}
