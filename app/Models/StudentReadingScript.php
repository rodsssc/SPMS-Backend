<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentReadingScript extends Model
{
    protected $fillable = [
        'student_id',
        'reading_script_id',
        'status',
        'completed_at',
    ];
}
