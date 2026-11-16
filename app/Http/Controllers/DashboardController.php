<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $term = request('term', 'Term 1');
        $year = (int) request('year', now()->year);
        $classes = $user->role === 'admin'
            ? SchoolClass::withCount('students')->orderBy('name')->get()
            : $user->taughtClasses()->withCount('students')->orderBy('name')->get();

        if ($user->role === 'student') {
            $students = collect([$user->studentProfile()->with('schoolClass')->first()])->filter();
        } elseif ($user->role === 'parent') {
            $students = Student::with('schoolClass')->where('parent_phone', $user->phone)->get();
        } else {
            $students = collect();
        }

        $data = compact('classes', 'term', 'year', 'students');
        $data['schoolName'] = config('school.name');

        if ($user->role === 'admin') {
            $data += [
                'totalStudents' => Student::count(),
                'feesCollected' => FeePayment::where('term', $term)->where('academic_year', $year)->sum('amount'),
                'defaulters' => $this->defaulterCount($term, $year),
                'teachers' => User::where('role', 'teacher')->count(),
                'attendanceToday' => Attendance::whereDate('date', today())->whereIn('status', ['present', 'late'])->count(),
            ];
        } elseif ($user->role === 'teacher') {
            $classIds = $classes->modelKeys();
            $requiredMarks = $classes->sum(fn (SchoolClass $class) => $class->students_count
                * $class->subjects()->when($user->role === 'teacher', fn ($query) => $query
                    ->where(fn ($subjects) => $subjects->whereNull('teacher_id')->orWhere('teacher_id', $user->id)))->count());
            $enteredMarks = Result::whereHas('student', fn ($query) => $query->whereIn('class_id', $classIds))
                ->where('term', $term)->where('academic_year', $year)
                ->when($user->role === 'teacher', fn ($query) => $query->whereHas('subject', fn ($subjects) => $subjects
                    ->whereNull('teacher_id')->orWhere('teacher_id', $user->id)))
                ->count();
            $data['pendingMarks'] = max(0, $requiredMarks - $enteredMarks);
            $data['studentCount'] = Student::whereIn('class_id', $classIds)->count();
        } else {
            $data['studentSummaries'] = $students->map(fn (Student $student) => [
                'student' => $student,
                'paid' => FeePayment::where('student_id', $student->id)->where('term', $term)->where('academic_year', $year)->sum('amount'),
                'fee' => FeeStructure::where('class_id', $student->class_id)->where('term', $term)->where('academic_year', $year)->value('amount') ?? 0,
                'results' => Result::with('subject')->where('student_id', $student->id)->where('term', $term)->where('academic_year', $year)->get(),
                'attendanceTotal' => Attendance::where('student_id', $student->id)->count(),
                'attendancePresent' => Attendance::where('student_id', $student->id)->whereIn('status', ['present', 'late'])->count(),
            ]);
        }

        return view('dashboard', $data);
    }

    private function defaulterCount(string $term, int $year): int
    {
        return FeeStructure::with('schoolClass.students.feePayments')
            ->where('term', $term)->where('academic_year', $year)->get()
            ->sum(fn ($structure) => $structure->schoolClass->students->filter(function ($student) use ($structure) {
                return (float) $student->feePayments->where('term', $structure->term)
                    ->where('academic_year', $structure->academic_year)->sum('amount') < (float) $structure->amount;
            })->count());
    }
}
