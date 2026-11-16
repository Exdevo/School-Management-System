<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subject::with(['schoolClass', 'teacher'])->orderBy('name');
        if ($request->user()->role === 'teacher') {
            $query->whereIn('class_id', $request->user()->taughtClasses()->select('classes.id'));
        }

        return view('subjects.index', [
            'subjects' => $query->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'teachers' => User::where('role', 'teacher')->orderBy('name')->get(),
            'canManage' => $request->user()->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);
        Subject::create($data);

        return back()->with('status', 'Subject added.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('status', 'Subject deleted.');
    }
}
