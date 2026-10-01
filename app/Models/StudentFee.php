<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    protected $table = 'student_fees';
    protected $guarded = [];

    public function structure() { return $this->belongsTo(FeeStructure::class, 'fee_structure_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function getBalanceAttribute() { return max(0, $this->amount_due - $this->amount_paid); }
}
