<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $table = 'results';
    protected $guarded = [];

    protected $casts = ['ca1'=>'float','ca2'=>'float','exam'=>'float','total'=>'float'];
    public function student() { return $this->belongsTo(Student::class); }
}
