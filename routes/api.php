<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['guard' => 'admin'], function () {
    Route::post('/login', 'API\auth\LoginController@login');
    Route::post('/register', 'API\auth\RegisterController@register');
});

Route::get('/lesson/{lessonId}/class', 'API\ClassesController@getClassByLessonId');
Route::post('/student/rollUp', 'API\StudentController@rollUp');
Route::get('/lessons/{id}/students-rolled-up', 'API\StudentController@getStudentsRolledUpByLessonId');
Route::get('/class/{lessonId}/students', 'API\StudentController@getAllStudentsByLessonId');

Route::group([
    'middleware' => 'auth:api'
], function() {
    Route::put('/student/roll-up/toggle', 'API\StudentController@toggleRollUp');
    Route::get('/me', 'API\auth\MeController@me');
    Route::get('/classes/{id}/students', 'API\StudentController@index');
    Route::get('/classes/{id}/students-roll-up', 'API\StudentController@getStudentsRolledUpByClassId');
    Route::get('classes/{classId}/lessons', 'API\LessonController@index');
    Route::post('/class/create', 'API\ClassesController@createClass');
    Route::post('/lesson/create', 'API\LessonController@createLesson');
    Route::post('/lesson/update', 'API\LessonController@updateLesson');
    Route::get('/classes', 'API\ClassesController@index');
    Route::delete('/lesson/{lessonId}/delete', 'API\LessonController@deleteLesson');
    Route::put('/lesson/{lessonId}/confirm', 'API\LessonController@confirmLesson');
    Route::post('/classes/{classId}/student/add', 'API\ClassesController@addStudentToClass');
    Route::post('/lessons/{lessonId}/set-start-roll-up-time', 'API\LessonController@setStartRollUpTime');
    Route::post('/lessons/{classId}/batch-create', 'API\LessonController@batchCreateLessons');
});
