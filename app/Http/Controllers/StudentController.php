<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with(['schoolClass', 'user']);
        if ($request->user()->role === 'teacher') {
            $query->whereIn('class_id', $request->user()->taughtClasses()->select('classes.id'));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('admission_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                ->orWhere('parent_name', 'like', "%{$search}%"));
        }

        return view('students.index', [
            'students' => $query->orderBy('admission_number')->paginate(20)->withQueryString(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'unlinkedUsers' => User::where('role', 'student')->whereDoesntHave('studentProfile')->orderBy('name')->get(),
            'canManage' => $request->user()->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email', 'prohibited_with:user_id'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['nullable', 'required_with:email', 'string', 'min:8'],
            'user_id' => ['nullable', 'integer', 'prohibited_with:email', Rule::exists('users', 'id')->where('role', 'student'), Rule::unique('students', 'user_id')],
            'admission_number' => ['required', 'string', 'max:50', 'unique:students,admission_number'],
            'class_id' => ['required', 'exists:classes,id'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['Female', 'Male', 'Other'])],
            'dob' => ['required', 'date', 'before:today'],
        ]);

        DB::transaction(function () use ($data) {
            $user = isset($data['user_id']) ? User::findOrFail($data['user_id']) : null;
            if (! empty($data['email'])) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                    'role' => 'student',
                ]);
            }
            Student::create([
                'name' => $user?->name ?? $data['name'],
                'admission_number' => $data['admission_number'],
                'class_id' => $data['class_id'],
                'parent_name' => $data['parent_name'],
                'parent_phone' => $data['parent_phone'],
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'user_id' => $user?->id,
            ]);
        });

        return back()->with('status', 'Student added successfully.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student->id)],
            'class_id' => ['required', 'exists:classes,id'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['Female', 'Male', 'Other'])],
            'dob' => ['required', 'date', 'before:today'],
        ]);

        DB::transaction(function () use ($data, $student) {
            $student->update($data);
            $student->user?->update(['name' => $data['name']]);
        });

        return back()->with('status', 'Student record updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->feePayments()->exists()) {
            return back()->withErrors(['student' => 'This student has payment history and cannot be deleted.']);
        }
        $student->delete();

        return back()->with('status', 'Student record deleted.');
    }
}
