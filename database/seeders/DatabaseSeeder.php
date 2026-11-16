<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@school.test'], [
            'name' => 'School Administrator',
            'phone' => '+254700000001',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $teacher = User::updateOrCreate(['email' => 'teacher@school.test'], [
            'name' => 'Mary Teacher',
            'phone' => '+254700000002',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);
        $parent = User::updateOrCreate(['email' => 'parent@school.test'], [
            'name' => 'John Kamau',
            'phone' => '+254711000001',
            'password' => Hash::make('password'),
            'role' => 'parent',
        ]);
        $schoolClass = SchoolClass::updateOrCreate([
            'name' => 'Grade 7',
            'intake_year' => now()->year,
        ]);
        $schoolClass->teachers()->syncWithoutDetaching([$teacher->id]);

        $subjects = collect(['Mathematics', 'English', 'Kiswahili'])->mapWithKeys(fn (string $name) => [
            $name => Subject::updateOrCreate(
                ['class_id' => $schoolClass->id, 'name' => $name],
                ['teacher_id' => $teacher->id],
            ),
        ]);

        $students = collect([
            ['name' => 'John Kamau', 'admission' => 'G7-001', 'gender' => 'Male', 'phone' => '+254711000001'],
            ['name' => 'Amina Otieno', 'admission' => 'G7-002', 'gender' => 'Female', 'phone' => '+254711000002'],
            ['name' => 'Brian Mwangi', 'admission' => 'G7-003', 'gender' => 'Male', 'phone' => '+254711000003'],
            ['name' => 'Faith Wanjiku', 'admission' => 'G7-004', 'gender' => 'Female', 'phone' => '+254711000004'],
            ['name' => 'Kevin Kiptoo', 'admission' => 'G7-005', 'gender' => 'Male', 'phone' => '+254711000005'],
        ])->map(function (array $data, int $index) use ($schoolClass, $parent) {
            $studentUser = User::updateOrCreate(['email' => 'student'.($index + 1).'@school.test'], [
                'name' => $data['name'],
                'phone' => '+25472000000'.($index + 1),
                'password' => Hash::make('password'),
                'role' => 'student',
            ]);
            $student = Student::updateOrCreate(['admission_number' => $data['admission']], [
                'user_id' => $studentUser->id,
                'name' => $data['name'],
                'class_id' => $schoolClass->id,
                'parent_name' => $index === 0 ? $parent->name : 'Parent / Guardian '.($index + 1),
                'parent_phone' => $data['phone'],
                'gender' => $data['gender'],
                'dob' => now()->subYears(12)->subDays($index * 47)->toDateString(),
            ]);

            return $student;
        });

        FeeStructure::updateOrCreate([
            'class_id' => $schoolClass->id,
            'term' => 'Term 1',
            'academic_year' => now()->year,
        ], ['amount' => 15000]);

        foreach ([
            [$students[0], 5000, 'QW1A2B3C4D'],
            [$students[1], 10000, 'QW2A3B4C5E'],
            [$students[2], 15000, 'QW3A4B5C6F'],
        ] as [$student, $amount, $code]) {
            FeePayment::updateOrCreate([
                'mpesa_code' => $code,
            ], [
                'student_id' => $student->id,
                'amount' => $amount,
                'term' => 'Term 1',
                'academic_year' => now()->year,
                'payment_method' => 'M-Pesa',
                'paid_at' => today()->subDays(3),
                'received_by' => $admin->id,
            ]);
        }

        $marks = [
            [78, 65, 70],
            [84, 76, 80],
            [59, 62, 56],
            [91, 88, 85],
            [67, 72, 64],
        ];
        foreach ($students as $studentIndex => $student) {
            foreach ($subjects->values() as $subjectIndex => $subject) {
                $mark = $marks[$studentIndex][$subjectIndex];
                Result::updateOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'term' => 'Term 1',
                    'academic_year' => now()->year,
                ], [
                    'marks' => $mark,
                    'grade' => Result::gradeFor($mark),
                    'entered_by' => $teacher->id,
                ]);
            }
        }

        foreach ($students as $index => $student) {
            Attendance::updateOrCreate([
                'student_id' => $student->id,
                'date' => today(),
            ], [
                'class_id' => $schoolClass->id,
                'status' => $index === 2 ? 'absent' : ($index === 4 ? 'late' : 'present'),
                'marked_by' => $teacher->id,
            ]);
        }
    }
}
