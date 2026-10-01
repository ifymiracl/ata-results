<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCode extends Model
{
    protected $table = 'report_codes';
    protected $guarded = [];

    protected $primaryKey = 'code'; public $incrementing = false; protected $keyType = 'string';
    public function student() { return $this->belongsTo(Student::class); }
}
