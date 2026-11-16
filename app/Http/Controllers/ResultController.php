<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $term = $request->input('term', 'Term 1');
        $year = (int) $request->input('year', now()->year);
        $query = Result::with(['student.schoolClass', 'student.user', 'subject'])
            ->where('term', $term)->where('academic_year', $year);
        if ($user->role === 'student') {
            $query->where('student_id', $user->studentProfile()->value('id') ?? 0);
        } elseif ($user->role === 'parent') {
            $query->whereHas('student', fn ($q) => $q->where('parent_phone', $user->phone));
        } elseif ($user->role === 'teacher') {
            $query->whereHas('student', fn ($q) => $q->whereIn('class_id', $user->taughtClasses()->select('classes.id')));
        }

        return view('results.index', [
            'results' => $query->orderBy('student_id')->orderBy('subject_id')->paginate(50)->withQueryString(),
            'classes' => $user->role === 'admin'
                ? SchoolClass::orderBy('name')->get()
                : $user->taughtClasses()->orderBy('name')->get(),
            'term' => $term,
            'year' => $year,
            'canEnter' => in_array($user->role, ['admin', 'teacher'], true),
        ]);
    }

    public function create(Request $request): View
    {
        $classes = $request->user()->role === 'admin'
            ? SchoolClass::orderBy('name')->get()
            : $request->user()->taughtClasses()->orderBy('name')->get();
        $classId = $request->integer('class_id');
        $schoolClass = $classes->firstWhere('id', $classId);
        $subjects = $schoolClass
            ? $schoolClass->subjects()->with('teacher')->orderBy('name')->get()
            : collect();
        if ($request->user()->role === 'teacher') {
            $subjects = $subjects->filter(fn ($subject) => ! $subject->teacher_id || $subject->teacher_id === $request->user()->id);
        }

        return view('results.create', [
            'classes' => $classes,
            'selectedClass' => $schoolClass,
            'subjects' => $subjects,
            'students' => $schoolClass?->students()->orderBy('admission_number')->get() ?? collect(),
            'term' => $request->input('term', 'Term 1'),
            'year' => $request->input('year', now()->year),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'term' => ['required', Rule::in(['Term 1', 'Term 2', 'Term 3'])],
            'academic_year' => ['required', 'integer', 'between:2000,2100'],
            'marks' => ['required', 'array', 'min:1'],
            'marks.*' => ['nullable', 'numeric', 'between:0,100'],
        ]);
        $this->authorizeClass($request, (int) $data['class_id']);
        $subject = Subject::where('id', $data['subject_id'])->where('class_id', $data['class_id'])->firstOrFail();
        if ($request->user()->role === 'teacher' && $subject->teacher_id && $subject->teacher_id !== $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($data, $subject, $request) {
            foreach ($data['marks'] as $studentId => $marks) {
                $student = Student::where('id', $studentId)->where('class_id', $data['class_id'])->first();
                if (! $student || $marks === null || $marks === '') {
                    continue;
                }
                $numericMarks = (float) $marks;
                Result::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'term' => $data['term'],
                        'academic_year' => $data['academic_year'],
                    ],
                    [
                        'marks' => $numericMarks,
                        'grade' => Result::gradeFor($numericMarks),
                        'entered_by' => $request->user()->id,
                    ],
                );
            }
        });

        return redirect()->route('results.index')->with('status', 'Marks saved and grades calculated.');
    }

    public function report(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);
        $term = $request->input('term', 'Term 1');
        $year = (int) $request->input('year', now()->year);
        $results = $student->results()->with('subject')->where('term', $term)->where('academic_year', $year)->get();
        $total = (float) $results->sum('marks');
        $average = $results->isNotEmpty() ? $total / $results->count() : 0;
        $classTotals = Result::whereHas('student', fn ($q) => $q->where('class_id', $student->class_id))
            ->where('term', $term)->where('academic_year', $year)
            ->select('student_id', DB::raw('SUM(marks) as total'))->groupBy('student_id')
            ->orderByDesc('total')->get();
        $position = $classTotals->search(fn ($row) => $row->student_id === $student->id);

        return view('results.report', [
            'student' => $student->load(['user', 'schoolClass']),
            'results' => $results,
            'term' => $term,
            'year' => $year,
            'total' => $total,
            'average' => $average,
            'overallGrade' => $results->isEmpty() ? '-' : Result::gradeFor($average),
            'position' => $position === false ? null : $position + 1,
        ]);
    }

    public function downloadReport(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);
        $term = $request->input('term', 'Term 1');
        $year = (int) $request->input('year', now()->year);
        $results = $student->results()->with('subject')->where('term', $term)->where('academic_year', $year)->get();
        $total = (float) $results->sum('marks');
        $average = $results->isNotEmpty() ? $total / $results->count() : 0;
        $classTotals = Result::whereHas('student', fn ($q) => $q->where('class_id', $student->class_id))
            ->where('term', $term)->where('academic_year', $year)
            ->select('student_id', DB::raw('SUM(marks) as total'))->groupBy('student_id')->orderByDesc('total')->get();
        $position = $classTotals->search(fn ($row) => $row->student_id === $student->id);

        return Pdf::loadView('results.report-pdf', [
            'student' => $student->load(['user', 'schoolClass']),
            'results' => $results,
            'term' => $term,
            'year' => $year,
            'total' => $total,
            'average' => $average,
            'overallGrade' => $results->isEmpty() ? '-' : Result::gradeFor($average),
            'position' => $position === false ? null : $position + 1,
            'schoolName' => config('school.name'),
        ])->download('report-card-'.$student->admission_number.'-'.$term.'-'.$year.'.pdf');
    }

    private function authorizeClass(Request $request, int $classId): void
    {
        if ($request->user()->role === 'teacher') {
            abort_unless($request->user()->taughtClasses()->where('classes.id', $classId)->exists(), 403);
        }
    }

    private function authorizeStudent(Request $request, Student $student): void
    {
        $user = $request->user();
        $allowed = $user->role === 'admin'
            || ($user->role === 'student' && $student->user_id === $user->id)
            || ($user->role === 'parent' && $student->parent_phone === $user->phone)
            || ($user->role === 'teacher' && $user->taughtClasses()->where('classes.id', $student->class_id)->exists());
        abort_unless($allowed, 403);
    }
}
