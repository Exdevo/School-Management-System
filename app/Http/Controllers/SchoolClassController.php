<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        $classes = $request->user()->role === 'admin'
            ? SchoolClass::with('teachers')->withCount('students')->orderBy('name')->get()
            : $request->user()->taughtClasses()->with('teachers')->withCount('students')->orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $myClassIds = $request->user()->taughtClasses()->pluck('classes.id')->all();

        return view('classes.index', compact('classes', 'teachers', 'myClassIds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'intake_year' => ['required', 'integer', 'between:2000,2100'],
            'teacher_ids' => ['array'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);

        DB::transaction(function () use ($data) {
            $class = SchoolClass::create(['name' => $data['name'], 'intake_year' => $data['intake_year']]);
            $class->teachers()->sync($data['teacher_ids'] ?? []);
        });

        return back()->with('status', 'Class created.');
    }

    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'intake_year' => ['required', 'integer', 'between:2000,2100'],
            'teacher_ids' => ['array'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);

        DB::transaction(function () use ($data, $schoolClass) {
            $schoolClass->update(['name' => $data['name'], 'intake_year' => $data['intake_year']]);
            $schoolClass->teachers()->sync($data['teacher_ids'] ?? []);
        });

        return back()->with('status', 'Class and teacher assignments updated.');
    }

    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->delete();

        return back()->with('status', 'Class deleted.');
    }
}
