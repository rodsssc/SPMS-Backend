<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'adviser_name',
        'year_level',
        'section_name',
        'school_year',
        'status'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
