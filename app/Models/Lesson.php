<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['class_id', 'start_time', 'end_time'];

    public function students()
    {
        return $this->belongsToMany('App\Models\Student', 'lesson_student')
            ->withTimestamps();
    }

    public function class()
    {
        return $this->belongsTo('App\Models\_Class', 'class_id');
    }
}
