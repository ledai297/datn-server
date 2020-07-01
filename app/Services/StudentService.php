<?php

namespace App\Services;
use App\Events\NotifyRollUpSuccess;
use App\Models\_Class;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\Student;
use App\Models\Lesson;
use Pusher\Pusher;

class StudentService
{
    protected $student;
    protected $_class;
    protected $lesson;

    public function __construct(Student $student, _Class $_class, Lesson $lesson)
    {
        $this->student = $student;
        $this->_class = $_class;
        $this->lesson = $lesson;
    }

    public function isStudentExist($id)
    {
        return $this->student->find($id);
    }

    public function getById($id)
    {
        return $this->student->find($id);
    }

    public function getByStudentCode($studentCode)
    {
        return $this->student->where('student_code', '=', $studentCode);
    }

    public function rollUp($studentCode, $lessonId, $deviceId)
    {
        $currentLesson = Lesson::find($lessonId);
        $currentStudent = $this->student->where('student_code', '=', $studentCode)->first();

        if (!$currentLesson) {
            return [
                'error'     =>      'Lớp học không tồn tại',
                'code'      =>      404,
            ];
        }

        if (!$currentStudent) {
            return [
                'error'     =>      'Sinh viên không tồn tại',
                'code'      =>      404,
            ];
        }

        if (!$this->isStudentBelongToClass($currentLesson->class->id, $currentStudent->id)) {
            return [
                'error'     =>      'Sinh viên không có trong danh sách lớp này',
                'code'      =>      404,
            ];
        }

//        if (!$this->isValidateRangeOfTime($currentLesson->start_roll_up_time)) {
//            return [
//                'error'     =>      'Hết thời gian điểm danh',
//                'code'      =>      400,
//            ];
//        }

        if ($currentLesson->students()->where('students.id', $currentStudent->id)->exists()) {
            return [
                'error'     =>      'Sinh viên đã điểm danh trước đó',
                'code'      =>      400,
            ];
        }

        $device = $currentLesson
            ->students()
            ->wherePivot('device_id', '=', $deviceId)
            ->get()
            ->toArray();

        Log::info(count($device));
        if (count($device) != 0) {
            return [
                'error'     =>      'Thiết bi này đã dùng để điểm danh cho tiết học này',
                'code'      =>      429,
            ];
        }

        $currentLesson->students()->attach($currentStudent->id, ['device_id' => $deviceId]);
        event(new NotifyRollUpSuccess('success'));

        return [
            'message'       =>      'Sinh viên điểm danh thành công',
            'code'          =>      201,
        ];
    }

    public function getStudentByClassId($params)
    {
        $data = Student::where('class_id', '=', $params['class_id'])
            ->Where('name', 'LIKE', '$'.$params['search_key'].'%')
            ->orWhere('student_code', 'LIKE', '%'.$params['search_key'].'%')
            ->paginate($params['per_page'], ['*'], 'current_page', $params['current_page']);
        $students = json_decode(json_encode($data), true);
        return [
            'total'         =>      $students['total'],
            'current_page'  =>      $students['current_page'],
            'data'          =>      $students['data'],
            'per_page'      =>      $students['per_page'],
        ];
    }

    public function getStudentsRolledUpByLessonId($lessonId, $keySearch, $rollUpFilter)
    {
        Log::info('akdjds'.$rollUpFilter);
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return [
                'error'     =>      'Không tìm thấy buổi học tương ứng',
                'code'      =>      404
            ];
        }
        $currentClass = $this->_class
            ->find($lesson->class_id);
        $allClassStudents = $currentClass
            ->students()
            ->where('student_code', 'LIKE', '%'.$keySearch.'%')
            ->get();
        $studentRolledUpInLesson = $lesson
            ->students()
            ->where('student_code', 'LIKE', '%'.$keySearch.'%')
            ->get();

        $lessonDetail = [
            'id'            =>      $lesson->id,
            'start_time'    =>      $lesson->start_time,
            'end_time'      =>      $lesson->end_time,
            'subject_name'  =>      $currentClass->subject_name,
            'is_confirmed'  =>      $lesson->is_confirmed,
            'subject_code'  =>      $currentClass->subject_code,
            'class_id'      =>      $currentClass->id,
        ];


        if (count($allClassStudents) == 0) {
            return [];
        }

        foreach ($allClassStudents as $key=>$student) {
            if (in_array($student->id, $studentRolledUpInLesson->pluck('id')->toArray())) {
                $allClassStudents[$key]['is_rolled_up'] = 1;
            } else {
                $allClassStudents[$key]['is_rolled_up'] = 0;
            }
        }


        $result = [];
        if ($rollUpFilter == 1) {
            for ($i = 0; $i < count($allClassStudents->toArray()); $i += 1) {
                if ($allClassStudents[$i]['is_rolled_up'] == 1) {
                    array_push($result, $allClassStudents[$i]);
                }
            }
        } else if ($rollUpFilter == 0) {
            for ($i = 0; $i < count($allClassStudents->toArray()); $i += 1) {
                if ($allClassStudents[$i]['is_rolled_up'] == 0) {
                    array_push($result, $allClassStudents[$i]);
                }
            }
        } else {
            $result = $allClassStudents;
        }

        return [
            'students'      =>      $result,
            'detail'        =>      $lessonDetail,
        ];
    }

    public function getStudentRolledUpByClassId($classId, $searchKey)
    {
        $currentClass = _Class::find($classId);
        Log::info('searchKey: '.$searchKey);

        $allClassStudents = $currentClass
            ->students()
            ->where('student_code', 'LIKE', '%'.$searchKey.'%')
            ->get();

        foreach ($allClassStudents as $key=>$student) {
            $allClassStudents[$key]->rollUps = [];
        }

        $lessonDate = [];

        foreach ($currentClass->lessons as $lesson) {
            $studentIdsRolledUp = $lesson->students->pluck('id')->toArray();
            array_push($lessonDate, $lesson->start_time);
            foreach ($allClassStudents as $key=>$student) {
                $isRolledUp = in_array($student->id, $studentIdsRolledUp) ? 1 : 0;
                $studentRollUps = $student->rollUps;
                array_push($studentRollUps, $isRolledUp);
                $student->rollUps = $studentRollUps;
                $allClassStudents[$key] = $student;
            }
        }

        return [
            'students'       =>      $allClassStudents,
            'lessons_date'   =>      $lessonDate,
        ];
    }

    public function getClassStudentsByLessonId($lessonId, $searchKey)
    {
        $currentLesson = $this->lesson->find($lessonId);

        if (!$currentLesson) {
            return [
                'error'     =>      'Không tìm thấy lớp học',
                'code'      =>      '404',
            ];
        }

        $currentClass = $currentLesson->class;

        return [
            'data'  =>      $this->getStudentRolledUpByClassId($currentClass->id, $searchKey),
            'code'  =>      200,
        ];
    }

    public function toggleRollUp($studentId, $lessonId)
    {
        $currentStudent = $this->student->findOrFail($studentId);
        if (!$currentStudent) {
            return [
              'error'   =>  'Không tìm thấy sinh viên',
              'code'    =>  404,
            ];
        }

        $currentStudent->lessons()->toggle($lessonId);

        return [
            'message'       =>      'Thay đổi trạng thái điểm danh của sinh viên thành công',
            'code'          =>      200,
        ];
    }

    protected function isValidateRangeOfTime($startRollUpTime)
    {
        $start = Carbon::parse($startRollUpTime);
        $now = Carbon::now();
        return  $start->addMinutes(15)->timestamp > $now->timestamp;
    }

    protected function isStudentBelongToClass($classId, $studentId)
    {
        $currentClass = $this->_class->find($classId);
        return $currentClass->students()->where('students.id', $studentId)->exists();
    }
}