<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    protected $fillable = ['name', 'student_code', 'class_name', 'birth_day'];

    public function lessons()
    {
        return $this->belongsToMany('App\Models\Lesson', 'lesson_student');
    }

    public function classes()
    {
        return $this->belongsToMany('App\Models\_Class', 'class_student', 'student_id', 'class_id');
    }
}
