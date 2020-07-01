<?php


namespace App\Services;


use App\Models\_Class;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassesService
{
    protected $_class;
    protected $user;

    public function __construct(_Class $_class, User $user)
    {
        $this->_class = $_class;
        $this->user = $user;
    }

    public function getClassByUserId($params)
    {
        $classesObject = $this->user
            ->classes()
            ->Where('class_code', 'LIKE', '%'.$params['key_search'].'%')
            ->orWhere('subject_name', 'LIKE', '%'.$params['key_search'].'%')
            ->orWhere('subject_code', 'LIKE', '%'.$params['key_search'].'%')
            ->paginate($params['per_page'], ['*'], 'current_page', $params['current_page']);
        $classesData = json_decode(json_encode($classesObject, true), true);

        return [
            'data'          =>      $classesData['data'],
            'total'         =>      $classesData['total'],
            'per_page'      =>      $classesData['per_page'],
            'current_page'  =>      $classesData['current_page'],
        ];
    }

    public function createClass($data, $userId)
    {
        $user = $this->user->find($userId);
        $newClass = new _Class();
        $newClass->subject_name = $data['subject_name'];
        $newClass->subject_code = $data['subject_code'];
        $newClass->class_code   = $data['class_code'];

        $user->classes()->save($newClass);
    }

    public function getById($id)
    {
        return $this->_class->findOrFail($id);
    }

    public function addStudentToClass($request)
    {
        $currentUser = User::find($request->user()->id);
        $userClassIds = $currentUser->classes->pluck('id')->toArray();

        if (!in_array($request->classId, $userClassIds)) {
            return [
                'error'     =>      'Giáo viên không quản lý lớp học này',
                'code'      =>      403,
            ];
        }

        $currentClass = _Class::find($request->class_id);

        if (!$currentClass) {
            return [
                'error'     =>      'Không tìm thấy lớp học này',
                'code'      =>      404,
            ];
        }

        $currentStudent = Student::where('student_code', '=', $request->student_code)->first();

        if (!$currentStudent) {
            return [
                'error'     =>      'Mã số sinh viên không đúng',
                'code'      =>      404,
            ];
        }

        $studentExist = $currentClass->students()
            ->where('student_code', '=', $request->student_code)
            ->get()
            ->toArray();

        if (count($studentExist) != 0) {
            return [
                'error'     =>      'Sinh viên đã có trong danh sách lớp',
                'code'      =>      409,
            ];
        }
        $currentClass->students()->attach($currentStudent['id']);
        return [
            'message'       =>      'Thêm sinh viên vào lớp thành công',
            'code'          =>      201,
        ];
    }
}
