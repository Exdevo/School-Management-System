<?php

namespace Tests\Feature;

use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_and_management_screens_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = SchoolClass::create(['name' => 'Grade 7', 'intake_year' => 2026]);
        $student = Student::create([
            'name' => 'Amina Otieno',
            'admission_number' => 'G7-001',
            'class_id' => $class->id,
            'parent_name' => 'Parent',
            'parent_phone' => '+254700000004',
            'gender' => 'Female',
            'dob' => '2013-01-01',
        ]);
        FeeStructure::create([
            'class_id' => $class->id,
            'term' => 'Term 1',
            'academic_year' => 2026,
            'amount' => 15000,
        ]);
        Subject::create(['class_id' => $class->id, 'name' => 'Mathematics']);
        $this->actingAs($admin);

        foreach ([
            route('dashboard'),
            route('students.index'),
            route('classes.index'),
            route('subjects.index'),
            route('results.index'),
            route('attendance.index'),
            route('fees.index'),
            route('users.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('results.report', $student))->assertOk();
    }

    public function test_admin_can_create_and_update_student_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = SchoolClass::create(['name' => 'Form 1', 'intake_year' => 2026]);

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Wanjiku Njeri',
            'admission_number' => 'F1-101',
            'class_id' => $class->id,
            'parent_name' => 'Grace Njeri',
            'parent_phone' => '+254700000009',
            'gender' => 'Female',
            'dob' => '2013-04-10',
        ])->assertRedirect();

        $student = Student::where('admission_number', 'F1-101')->firstOrFail();
        $this->put(route('students.update', $student), [
            'name' => 'Wanjiku Grace Njeri',
            'admission_number' => 'F1-101',
            'class_id' => $class->id,
            'parent_name' => 'Grace Njeri',
            'parent_phone' => '+254700000009',
            'gender' => 'Female',
            'dob' => '2013-04-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Wanjiku Grace Njeri']);
    }

    public function test_admin_can_record_a_payment_with_an_automatic_balance_receipt(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = SchoolClass::create(['name' => 'Form 1', 'intake_year' => 2026]);
        $student = Student::create([
            'name' => 'Njeri Wambui',
            'admission_number' => 'F1-001',
            'class_id' => $class->id,
            'parent_name' => 'Parent',
            'parent_phone' => '+254700000003',
            'gender' => 'Female',
            'dob' => '2013-01-01',
        ]);
        FeeStructure::create([
            'class_id' => $class->id,
            'term' => 'Term 1',
            'academic_year' => 2026,
            'amount' => 15000,
        ]);

        $response = $this->actingAs($admin)->post(route('fees.payments.store'), [
            'student_id' => $student->id,
            'amount' => 5000,
            'term' => 'Term 1',
            'academic_year' => 2026,
            'payment_method' => 'M-Pesa',
            'mpesa_code' => 'TEST123456',
            'paid_at' => today()->toDateString(),
        ]);

        $response->assertRedirect(route('fees.receipt', FeePayment::first()));
        $this->assertDatabaseHas('fee_payments', ['student_id' => $student->id, 'amount' => 5000]);
        $this->assertSame(1, FeePayment::count());
        $this->get(route('fees.receipt', FeePayment::first()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_can_enter_marks_for_assigned_class_and_grade_is_calculated(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = SchoolClass::create(['name' => 'Grade 7', 'intake_year' => 2026]);
        $class->teachers()->attach($teacher);
        $student = Student::create([
            'name' => 'Amina Otieno',
            'admission_number' => 'G7-001',
            'class_id' => $class->id,
            'parent_name' => 'Parent',
            'parent_phone' => '+254700000004',
            'gender' => 'Female',
            'dob' => '2013-01-01',
        ]);
        $subject = Subject::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'name' => 'Mathematics',
        ]);

        $this->actingAs($teacher)->post(route('results.store'), [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'term' => 'Term 1',
            'academic_year' => 2026,
            'marks' => [$student->id => 75],
        ])->assertRedirect(route('results.index'));

        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'marks' => 75,
            'grade' => 'B',
        ]);
        $this->get(route('results.download', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_student_cannot_view_another_students_report_card(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $class = SchoolClass::create(['name' => 'Grade 7', 'intake_year' => 2026]);
        $otherStudent = Student::create([
            'name' => 'Other Student',
            'admission_number' => 'G7-002',
            'class_id' => $class->id,
            'parent_name' => 'Parent',
            'parent_phone' => '+254700000005',
            'gender' => 'Male',
            'dob' => '2013-01-01',
        ]);

        $this->actingAs($studentUser)
            ->get(route('results.report', $otherStudent))
            ->assertForbidden();
    }

    public function test_teacher_attendance_records_are_saved_and_absence_sms_is_logged(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = SchoolClass::create(['name' => 'Grade 7', 'intake_year' => 2026]);
        $class->teachers()->attach($teacher);
        $student = Student::create([
            'name' => 'Brian Mwangi',
            'admission_number' => 'G7-003',
            'class_id' => $class->id,
            'parent_name' => 'Parent',
            'parent_phone' => '+254700000006',
            'gender' => 'Male',
            'dob' => '2013-01-01',
        ]);
        Log::spy();

        $this->actingAs($teacher)->post(route('attendance.store'), [
            'class_id' => $class->id,
            'date' => today()->toDateString(),
            'attendance' => [$student->id => 'absent'],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'absent']);
        Log::shouldHaveReceived('info')->once();
    }

    public function test_parent_dashboard_and_fee_history_only_show_linked_children(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+254700000007']);
        $class = SchoolClass::create(['name' => 'Grade 7', 'intake_year' => 2026]);
        $child = Student::create([
            'name' => 'Njeri Wambui',
            'admission_number' => 'G7-004',
            'class_id' => $class->id,
            'parent_name' => $parent->name,
            'parent_phone' => $parent->phone,
            'gender' => 'Female',
            'dob' => '2013-01-01',
        ]);
        Student::create([
            'name' => 'Other Student',
            'admission_number' => 'G7-005',
            'class_id' => $class->id,
            'parent_name' => 'Other Parent',
            'parent_phone' => '+254700000008',
            'gender' => 'Male',
            'dob' => '2013-01-01',
        ]);

        $this->actingAs($parent)->get(route('dashboard'))
            ->assertOk()->assertSee('Njeri Wambui')->assertDontSee('Other Student');
        $this->get(route('fees.index'))
            ->assertOk()->assertSee('Njeri Wambui')->assertDontSee('Other Student');
        $this->get(route('results.report', $child))->assertOk();
    }
}
