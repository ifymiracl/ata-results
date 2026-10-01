<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualResult extends Model
{
    protected $table = 'annual_results';
    protected $guarded = [];

    protected $casts = ['applied'=>'boolean'];
    public function student() { return $this->belongsTo(Student::class); }
}
