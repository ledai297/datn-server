<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\LessonService;
use App\Services\StudentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    protected $studentService;
    protected $lessonService;

    public function __construct(StudentService $studentService, LessonService $lessonService)
    {
        $this->studentService = $studentService;
        $this->lessonService = $lessonService;
    }

    public function index(Request $request)
    {
        $params = [
            'class_id'      =>      $request->class_id,
            'search_key'    =>      $request->search_key ? $request->search_key : '',
            'per_page'      =>      $request->per_page ? $request->per_page : 10,
            'current_page'  =>      $request->current_page ? $request->current_page : 1,
        ];
        $students = $this->studentService->getStudentByClassId($params);
        return response()->json($students, 200);
    }

    public function getStudentsRolledUpByLessonId(Request $request)
    {
        $keySearch = $request->key_search ? $request->key_search : '';
        $data = $this->studentService->getStudentsRolledUpByLessonId($request->id, $keySearch, $request->roll_up);
        return response()->json($data, 200);
    }

    public function getStudentsRolledUpByClassId(Request $request)
    {
        $studentsData = $this->studentService->getStudentRolledUpByClassId($request->id, $request->search_key);
        return response()->json([
            'students'      =>      $studentsData['students'],
            'lessons_date'  =>      $studentsData['lessons_date'],
        ], 200);
    }

    public function getAllStudentsByLessonId(Request $request)
    {
        $allStudents = $this->studentService->getClassStudentsByLessonId($request->lessonId, $request->search_key);
        return response()->json($allStudents, 200);
    }

    public function rollUp(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getRules());

         if ($validator->fails()) {
             return response()->json(['error'=>$validator->errors()->first()], 422);
         }

        $data = $this->studentService->rollUp($request->student_code, $request->lesson_id, $request->device_id);

        return response()->json($data, 200);
    }

    public function toggleRollUp(Request $request)
    {
        $data = $this->studentService->toggleRollUp($request->student_id, $request->lesson_id);
        return response()->json($data, 200);
    }

    protected function getRules()
    {
        return [
          'student_code'    =>  'required',
          'lesson_id'       =>   'required',
        ];
    }

    protected function getParams($request)
    {
        return [
            'class_id'       =>      $request->id,
            'per_page'      =>      $request->per_page,
            'current_page'  =>      $request->current_page,
        ];
    }
}
