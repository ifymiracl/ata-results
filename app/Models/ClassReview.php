<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassReview extends Model
{
    protected $table = 'class_reviews';
    protected $guarded = [];

    protected $casts = ['reviewed_at'=>'datetime','published_at'=>'datetime'];
}
