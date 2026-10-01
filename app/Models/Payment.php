<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $guarded = [];

    public function fee() { return $this->belongsTo(StudentFee::class, 'student_fee_id'); }
}
