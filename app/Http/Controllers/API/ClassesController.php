<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ClassesService;
use App\Services\LessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClassesController extends Controller
{
    protected $classService;
    protected $lessonService;

    public function __construct(ClassesService $classService, LessonService $lessonService)
    {
        $this->classService = $classService;
        $this->lessonService = $lessonService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error'=>'Permission denied'], 403);
        }

        $params = [
            'id'            =>      $userId,
            'current_page'  =>      $request->current_page ? $request->current_page : 1,
            'per_page'      =>      $request->per_page ? $request->per_page : 10,
            'key_search'    =>      $request->key_search ? $request->key_search : '',
        ];

        $data = $this->classService->getClassByUserId($params);

        return response()->json($data, 200);
    }

    public function createClass(Request $request)
    {
        $userId = Auth::id();
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()->first()], 422);
        }

        if (!$userId) {
            return response()->json(['error'=>'Permission denied'], 403);
        }

        $this->classService->createClass($request->all(), $userId);

        return response()->json(['message'=>'Create class successfully'], 201);
    }

    public function getClassByLessonId(Request $request)
    {
        $lessonId = $request->lessonId;
        $classData = $this->lessonService->getClassByLessonId($lessonId);

        return response()->json($classData, 200);
    }

    public function addStudentToClass(Request $request)
    {
        $data = $this->classService->addStudentToClass($request);
        return response()->json($data, 200);
    }

    protected function rules()
    {
        return [
            'subject_name'  =>      'required|max:255',
            'subject_code'  =>      'required|max:255',
            'class_code'    =>      'required|max:255|unique:classes',
        ];
    }

    protected function getParams($request)
    {
        return [
          'per_page'        =>      $request->query('per_page') ? $request->query('per_page') : 10,
          'current_page'    =>      $request->query('current_page'),
        ];
    }
}
