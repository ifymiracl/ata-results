<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAssignment extends Model
{
    protected $table = 'staff_assignments';
    protected $guarded = [];
    public $timestamps = false;
    public function staff() { return $this->belongsTo(Staff::class); }
}
