<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicAward extends Model
{
    protected $table = 'academic_awards';
    protected $guarded = [];

    public function student() { return $this->belongsTo(Student::class); }
}
