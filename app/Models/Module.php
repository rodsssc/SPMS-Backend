<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /** @use HasFactory<\Database\Factories\ModuleFactory> */
    use HasFactory;
    protected $fillable = [
        'adviser_id',
        'title',
        'year_level',
        'description'
    ];


    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function readingScripts()
    {
        return $this->hasMany(ReadingScript::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class)->withTimestamps();
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}
