<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $classes = $user->role === 'admin'
            ? SchoolClass::withCount('students')->orderBy('name')->get()
            : $user->taughtClasses()->withCount('students')->orderBy('name')->get();
        $classId = $request->integer('class_id');
        $date = $request->input('date', today()->toDateString());
        $schoolClass = $classes->firstWhere('id', $classId);
        $students = $schoolClass
            ? $schoolClass->students()->with('user')->orderBy('admission_number')->get()
            : collect();
        $records = $students->isEmpty() ? collect() : Attendance::whereIn('student_id', $students->modelKeys())
            ->whereDate('date', $date)->get()->keyBy('student_id');

        $summary = $user->role === 'admin'
            ? $classes->map(fn (SchoolClass $class) => [
                'class' => $class,
                'present' => Attendance::where('class_id', $class->id)->whereDate('date', $date)->where('status', 'present')->count(),
                'absent' => Attendance::where('class_id', $class->id)->whereDate('date', $date)->where('status', 'absent')->count(),
                'late' => Attendance::where('class_id', $class->id)->whereDate('date', $date)->where('status', 'late')->count(),
            ])
            : collect();

        return view('attendance.index', compact('classes', 'classId', 'date', 'students', 'records', 'summary'));
    }

    public function store(Request $request, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'late'])],
        ]);
        $user = $request->user();
        if ($user->role === 'teacher') {
            abort_unless($user->taughtClasses()->where('classes.id', $data['class_id'])->exists(), 403);
        }

        $absentees = collect();
        DB::transaction(function () use ($data, $user, &$absentees) {
            foreach ($data['attendance'] as $studentId => $status) {
                $student = Student::where('id', $studentId)->where('class_id', $data['class_id'])->first();
                if (! $student) {
                    continue;
                }
                Attendance::updateOrCreate(
                    ['student_id' => $student->id, 'date' => $data['date']],
                    ['class_id' => $student->class_id, 'status' => $status, 'marked_by' => $user->id],
                );
                if ($status === 'absent') {
                    $absentees->push($student);
                }
            }
        });

        foreach ($absentees as $student) {
            $sms->send($student->parent_phone, sprintf(
                '%s was marked absent on %s. Please contact the school if this is unexpected.',
                $student->name,
                $data['date'],
            ));
        }

        return back()->with('status', 'Attendance saved.');
    }
}
