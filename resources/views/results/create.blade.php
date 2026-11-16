<x-app-layout>
    <div class="mb-6"><a href="{{ route('results.index') }}" class="text-sm font-semibold text-blue-700">← Results</a><h1 class="mt-2 text-2xl font-bold">Enter subject marks</h1><p class="mt-1 text-sm text-slate-500">Choose a class to load its students, then enter marks out of 100.</p></div>
    <section class="card mb-5"><form method="GET" action="{{ route('results.create') }}" class="grid gap-3 sm:grid-cols-4">
        <div><label class="label">Class</label><select class="field" name="class_id" onchange="this.form.submit()" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->name }}</option>@endforeach</select></div>
        <div><label class="label">Term</label><select class="field" name="term">@foreach(['Term 1','Term 2','Term 3'] as $option)<option @selected($term === $option)>{{ $option }}</option>@endforeach</select></div>
        <div><label class="label">Academic year</label><input class="field" type="number" name="year" value="{{ $year }}" min="2000" max="2100"></div>
        <div class="flex items-end"><button class="btn-secondary w-full">Load class</button></div>
    </form></section>
    @if($selectedClass)
        @if($subjects->isEmpty())<div class="card text-sm text-slate-500">No subjects are assigned to {{ $selectedClass->name }} yet. Ask an administrator to add them.</div>
        @elseif($students->isEmpty())<div class="card text-sm text-slate-500">No students are enrolled in this class.</div>
        @else
            <section class="card"><div class="mb-5"><p class="text-sm font-medium text-blue-700">{{ $selectedClass->name }}</p><h2 class="mt-1 text-lg font-bold">Student marks</h2></div>
                <form method="POST" action="{{ route('results.store') }}">@csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClass->id }}"><input type="hidden" name="term" value="{{ $term }}"><input type="hidden" name="academic_year" value="{{ $year }}">
                    <div class="mb-5 max-w-sm"><label class="label">Subject</label><select class="field" name="subject_id" required><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->teacher ? ' · '.$subject->teacher->name : '' }}</option>@endforeach</select></div>
                    <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Admission no.</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Marks / 100</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($students as $student)<tr><td class="table-cell">{{ $student->admission_number }}</td><td class="table-cell font-semibold">{{ $student->name }}</td><td class="table-cell"><input class="field max-w-36" type="number" name="marks[{{ $student->id }}]" min="0" max="100" step="0.01" value="{{ old('marks.'.$student->id) }}" placeholder="Not entered"></td></tr>@endforeach</tbody></table></div>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-500">Leave a field blank to keep that student's mark unchanged.</p><button class="btn-primary">Save marks & calculate grades</button></div>
                </form>
            </section>
        @endif
    @endif
</x-app-layout>
