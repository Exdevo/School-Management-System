<x-app-layout>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-blue-700">{{ now()->format('l, d F Y') }}</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Here’s your school snapshot for {{ $term }}, {{ $year }}.</p>
        </div>
        @if(in_array(auth()->user()->role, ['admin', 'student', 'parent']))
            <form method="GET" class="flex items-center gap-2">
                <select name="term" class="field w-auto">
                    @foreach(['Term 1', 'Term 2', 'Term 3'] as $option)<option @selected($term === $option)>{{ $option }}</option>@endforeach
                </select>
                <input name="year" type="number" value="{{ $year }}" min="2000" max="2100" class="field w-24">
                <button class="btn-secondary">View</button>
            </form>
        @endif
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['Students', number_format($totalStudents), 'Across all classes', 'bg-blue-50 text-blue-700'],
                ['Fees collected', 'KSh '.number_format((float) $feesCollected), "$term $year", 'bg-emerald-50 text-emerald-700'],
                ['Fee defaulters', number_format($defaulters), 'Students with balance', 'bg-amber-50 text-amber-700'],
                ['Teachers', number_format($teachers), 'Active accounts', 'bg-violet-50 text-violet-700'],
                ['Present today', number_format($attendanceToday), now()->format('d M Y'), 'bg-cyan-50 text-cyan-700'],
            ] as [$label, $value, $hint, $colors])
                <div class="card"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-3 text-2xl font-bold text-slate-900">{{ $value }}</p><p class="mt-2 text-xs {{ $colors }} inline-flex rounded-full px-2.5 py-1">{{ $hint }}</p></div>
            @endforeach
        </div>
        <div class="mt-6 grid gap-5 lg:grid-cols-3">
            <section class="card lg:col-span-2">
                <div class="mb-4 flex items-center justify-between"><div><h2 class="font-bold text-slate-900">Class overview</h2><p class="mt-1 text-sm text-slate-500">Enrollment and teacher assignments</p></div><a href="{{ route('classes.index') }}" class="text-sm font-semibold text-blue-700">Manage classes</a></div>
                <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Class</th><th class="px-4 py-3">Intake</th><th class="px-4 py-3">Students</th><th class="px-4 py-3">Teachers</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($classes as $class)<tr><td class="table-cell font-semibold">{{ $class->name }}</td><td class="table-cell">{{ $class->intake_year }}</td><td class="table-cell">{{ $class->students_count }}</td><td class="table-cell">{{ $class->teachers->count() }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Create your first class to get started.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <section class="card"><h2 class="font-bold text-slate-900">Quick actions</h2><div class="mt-4 grid gap-2">
                <a class="btn-primary" href="{{ route('students.index') }}">Add or find a student</a>
                <a class="btn-secondary" href="{{ route('fees.index') }}">Record a fee payment</a>
                <a class="btn-secondary" href="{{ route('attendance.index') }}">Take attendance</a>
                <a class="btn-secondary" href="{{ route('users.index') }}">Manage staff & parents</a>
            </div></section>
        </div>
    @elseif(auth()->user()->role === 'teacher')
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card"><p class="text-sm text-slate-500">My classes</p><p class="mt-2 text-3xl font-bold">{{ $classes->count() }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">My students</p><p class="mt-2 text-3xl font-bold">{{ number_format($studentCount) }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">Marks entered this term</p><p class="mt-2 text-3xl font-bold">{{ number_format($pendingMarks) }}</p><p class="text-xs text-slate-500">Subject marks recorded</p></div>
        </div>
        <section class="card mt-6"><div class="mb-4 flex items-center justify-between"><div><h2 class="font-bold">My classes</h2><p class="mt-1 text-sm text-slate-500">Your assigned classes and enrolled learners</p></div><a href="{{ route('results.create') }}" class="btn-primary">Enter marks</a></div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">@forelse($classes as $class)<a href="{{ route('attendance.index', ['class_id' => $class->id]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-blue-300 hover:bg-blue-50/50"><p class="font-semibold">{{ $class->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $class->students_count }} students · Intake {{ $class->intake_year }}</p></a>@empty<p class="text-sm text-slate-500">No class assignments yet.</p>@endforelse</div>
        </section>
        <div class="mt-5 flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route('attendance.index') }}">Take attendance</a><a class="btn-secondary" href="{{ route('results.index') }}">Review results</a><a class="btn-secondary" href="{{ route('students.index') }}">View students</a></div>
    @else
        @if($studentSummaries->isEmpty())
            <div class="card"><h2 class="font-semibold text-slate-900">Your student record is not linked yet</h2><p class="mt-2 text-sm text-slate-500">Please contact the school administrator to connect your account with a student record.</p></div>
        @else
            <div class="space-y-5">
                @foreach($studentSummaries as $summary)
                    @php($student = $summary['student'])
                    @php($balance = max(0, (float) $summary['fee'] - (float) $summary['paid']))
                    <section class="card">
                        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-sm font-medium text-blue-700">{{ $student->schoolClass->name }} · {{ $student->admission_number }}</p><h2 class="mt-1 text-xl font-bold">{{ $student->name }}</h2></div>
                            <div class="flex flex-wrap gap-2"><a class="btn-secondary" href="{{ route('fees.index', ['term' => $term, 'year' => $year]) }}">Fee history</a><a class="btn-primary" href="{{ route('results.report', [$student, 'term' => $term, 'year' => $year]) }}">View report card</a></div>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-blue-50 p-4"><p class="text-xs font-semibold uppercase text-blue-700">Fee balance · {{ $term }}</p><p class="mt-2 text-xl font-bold text-blue-950">KSh {{ number_format($balance, 2) }}</p></div>
                            <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs font-semibold uppercase text-emerald-700">Results entered</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ $summary['results']->count() }} subjects</p></div>
                            <div class="rounded-xl bg-violet-50 p-4"><p class="text-xs font-semibold uppercase text-violet-700">Attendance</p><p class="mt-2 text-xl font-bold text-violet-950">{{ $summary['attendanceTotal'] ? number_format($summary['attendancePresent'] / $summary['attendanceTotal'] * 100, 1) : '—' }}%</p></div>
                        </div>
                        @if($summary['results']->isNotEmpty())
                            <div class="mt-5 overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Marks</th><th class="px-4 py-3">Grade</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($summary['results'] as $result)<tr><td class="table-cell">{{ $result->subject->name }}</td><td class="table-cell">{{ number_format((float) $result->marks, 1) }}%</td><td class="table-cell font-semibold">{{ $result->grade }}</td></tr>@endforeach</tbody></table></div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    @endif
</x-app-layout>
