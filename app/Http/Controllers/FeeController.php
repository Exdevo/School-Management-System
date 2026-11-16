<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\SmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(): View
    {
        $term = request('term', 'Term 1');
        $year = (int) request('year', now()->year);
        if (in_array(request()->user()->role, ['student', 'parent'], true)) {
            $studentQuery = Student::with([
                'schoolClass.feeStructures' => fn ($query) => $query->where('term', $term)->where('academic_year', $year),
                'feePayments' => fn ($query) => $query->where('term', $term)->where('academic_year', $year),
            ]);
            if (request()->user()->role === 'student') {
                $studentQuery->where('user_id', request()->user()->id);
            } else {
                $studentQuery->where('parent_phone', request()->user()->phone);
            }
            $students = $studentQuery->orderBy('name')->get();

            return view('fees.mine', [
                'students' => $students,
                'term' => $term,
                'year' => $year,
                'balanceFor' => fn (Student $student) => max(
                    0,
                    (float) ($student->schoolClass->feeStructures->first()?->amount ?? 0)
                    - (float) $student->feePayments->sum('amount'),
                ),
            ]);
        }
        $structures = FeeStructure::with('schoolClass')->where('term', $term)->where('academic_year', $year)
            ->orderBy('class_id')->get();
        $payments = FeePayment::with('student.schoolClass')->where('term', $term)->where('academic_year', $year)
            ->latest('paid_at')->paginate(20)->withQueryString();
        $defaulters = Student::with(['schoolClass', 'feePayments' => fn ($query) => $query
            ->where('term', $term)->where('academic_year', $year)])
            ->whereHas('schoolClass.feeStructures', fn ($query) => $query->where('term', $term)->where('academic_year', $year))
            ->get()->filter(fn (Student $student) => $this->balanceFor($student, $term, $year) > 0);

        return view('fees.index', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'students' => Student::with('schoolClass')->orderBy('admission_number')->get(),
            'structures' => $structures,
            'payments' => $payments,
            'defaulters' => $defaulters,
            'term' => $term,
            'year' => $year,
            'balanceFor' => fn (Student $student) => $this->balanceFor($student, $term, $year),
        ]);
    }

    public function saveStructure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'term' => ['required', Rule::in(['Term 1', 'Term 2', 'Term 3'])],
            'academic_year' => ['required', 'integer', 'between:2000,2100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ]);
        FeeStructure::updateOrCreate(
            ['class_id' => $data['class_id'], 'term' => $data['term'], 'academic_year' => $data['academic_year']],
            ['amount' => $data['amount']],
        );

        return back()->with('status', 'Fee structure saved.');
    }

    public function storePayment(Request $request, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'term' => ['required', Rule::in(['Term 1', 'Term 2', 'Term 3'])],
            'academic_year' => ['required', 'integer', 'between:2000,2100'],
            'payment_method' => ['required', Rule::in(['M-Pesa', 'Cash', 'Bank'])],
            'mpesa_code' => ['nullable', 'required_if:payment_method,M-Pesa', 'string', 'max:30', 'unique:fee_payments,mpesa_code'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $payment = DB::transaction(function () use ($data, $request) {
            $student = Student::with('schoolClass')->lockForUpdate()->findOrFail($data['student_id']);
            $structure = FeeStructure::where('class_id', $student->class_id)
                ->where('term', $data['term'])->where('academic_year', $data['academic_year'])->first();
            if (! $structure) {
                throw ValidationException::withMessages([
                    'student_id' => 'Set the fee structure for this student’s class and term before recording payment.',
                ]);
            }

            $paid = (float) FeePayment::where('student_id', $student->id)
                ->where('term', $data['term'])->where('academic_year', $data['academic_year'])->sum('amount');
            $balance = (float) $structure->amount - $paid;
            if ((float) $data['amount'] > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the remaining balance of KSh '.number_format(max(0, $balance), 2).'.',
                ]);
            }

            return FeePayment::create([
                ...$data,
                'received_by' => $request->user()->id,
            ])->load('student');
        });

        $remaining = $this->balanceFor($payment->student, $payment->term, $payment->academic_year);
        $sms->send($payment->student->parent_phone, sprintf(
            'Thank you, fee of KSh %s received for %s. Balance KSh %s.',
            number_format((float) $payment->amount, 2),
            $payment->student->name,
            number_format($remaining, 2),
        ));

        return redirect()->route('fees.receipt', $payment)->with('status', 'Payment recorded and receipt ready.');
    }

    public function receipt(Request $request, FeePayment $payment)
    {
        $payment->load('student.schoolClass');
        $this->authorizeStudent($request, $payment->student);
        $balance = $this->balanceFor($payment->student, $payment->term, $payment->academic_year);

        return Pdf::loadView('fees.receipt', [
            'payment' => $payment,
            'balance' => $balance,
            'schoolName' => config('school.name'),
        ])->download('receipt-'.$payment->id.'.pdf');
    }

    private function balanceFor(Student $student, string $term, int $year): float
    {
        $total = (float) FeeStructure::where('class_id', $student->class_id)
            ->where('term', $term)->where('academic_year', $year)->value('amount');
        $paid = (float) FeePayment::where('student_id', $student->id)
            ->where('term', $term)->where('academic_year', $year)->sum('amount');

        return max(0, $total - $paid);
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
