<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';
    protected $guarded = [];
    public $timestamps = false;
    public function subjects() { return $this->hasMany(DepartmentSubject::class); }
}
