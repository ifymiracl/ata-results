<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subjects';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['is_core' => 'boolean'];
}
