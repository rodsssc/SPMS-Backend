<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingScript extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'content',
        'word_count',
        'due_date',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}
