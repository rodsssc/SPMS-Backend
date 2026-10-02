<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_code',
        'section_id',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'birthdate',
        'gurdian_name',
        'gurdian_contact',
        'address',
        'status'
    ];

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function readingScripts()
    {
        return $this->belongsToMany(ReadingScript::class, 'student_reading_scripts')
            ->withPivot('status', 'completed_at')
            ->withTimestamps();
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}
