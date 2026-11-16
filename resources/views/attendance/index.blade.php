<x-app-layout>
    <div class="mb-6"><p class="text-sm font-medium text-blue-700">Daily register</p><h1 class="mt-1 text-2xl font-bold">Attendance</h1><p class="mt-1 text-sm text-slate-500">Record present, absent, or late for each student.</p></div>
    @if(auth()->user()->role === 'admin')
        <section class="card mb-5"><div class="mb-3"><h2 class="font-bold">Class summary</h2><p class="mt-1 text-sm text-slate-500">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</p></div><div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">@forelse($summary as $row)<div class="rounded-xl border border-slate-100 p-4"><p class="font-semibold">{{ $row['class']->name }}</p><p class="mt-2 text-sm"><span class="text-emerald-700">{{ $row['present'] }} present</span> · <span class="text-rose-700">{{ $row['absent'] }} absent</span></p><p class="mt-1 text-xs text-slate-500">{{ $row['late'] }} late · {{ $row['class']->students_count }} enrolled</p></div>@empty<p class="text-sm text-slate-500">No classes found.</p>@endforelse</div></section>
    @endif
    <section class="card mb-5"><form method="GET" action="{{ route('attendance.index') }}" class="grid gap-3 sm:grid-cols-3">
        <div><label class="label">Class</label><select class="field" name="class_id" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>@endforeach</select></div>
        <div><label class="label">Date</label><input class="field" type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}"></div>
        <div class="flex items-end"><button class="btn-secondary w-full">Load register</button></div>
    </form></section>
    @if($classId)
        @if($students->isEmpty())<div class="card text-sm text-slate-500">No students are enrolled in this class.</div>
        @else
            <section class="card"><form method="POST" action="{{ route('attendance.store') }}">@csrf<input type="hidden" name="class_id" value="{{ $classId }}"><input type="hidden" name="date" value="{{ $date }}">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-bold">Class register</h2><p class="mt-1 text-sm text-slate-500">Existing attendance for this date is selected.</p></div><button class="btn-primary">Save attendance</button></div>
                <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Admission no.</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($students as $student)<tr><td class="table-cell">{{ $student->admission_number }}</td><td class="table-cell font-semibold">{{ $student->name }}</td><td class="table-cell"><select class="field max-w-48" name="attendance[{{ $student->id }}]"><option value="present" @selected(($records[$student->id]->status ?? 'present') === 'present')>Present</option><option value="absent" @selected(($records[$student->id]->status ?? '') === 'absent')>Absent</option><option value="late" @selected(($records[$student->id]->status ?? '') === 'late')>Late</option></select></td></tr>@endforeach</tbody></table></div>
            </form></section>
        @endif
    @endif
</x-app-layout>
