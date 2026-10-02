<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'student_id','reading_script_id', 'module_id', 'script', 'transcription',
        'accuracy', 'total_words', 'correct_words', 'incorrect_words', 'audio_path', 'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function readingScript()
    {
        return $this->belongsTo(ReadingScript::class);
    }
}
