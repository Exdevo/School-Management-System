<x-app-layout>
    <div class="mb-6"><p class="text-sm font-medium text-blue-700">Academic setup</p><h1 class="mt-1 text-2xl font-bold">Subjects</h1><p class="mt-1 text-sm text-slate-500">Subjects are assigned to a class and can be assigned to a teacher.</p></div>
    @if($canManage)<section class="card mb-6"><h2 class="font-bold">Add subject</h2><form method="POST" action="{{ route('subjects.store') }}" class="mt-4 grid gap-4 sm:grid-cols-3">@csrf
        <div><label class="label">Class</label><select class="field" name="class_id" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
        <div><label class="label">Subject name</label><input class="field" name="name" placeholder="Mathematics" required></div>
        <div><label class="label">Teacher (optional)</label><select class="field" name="teacher_id"><option value="">Any assigned class teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
        <div class="sm:col-span-3"><button class="btn-primary">Add subject</button></div>
    </form></section>@endif
    <section class="card overflow-hidden !p-0"><div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Teacher</th>@if($canManage)<th></th>@endif</tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($subjects as $subject)<tr><td class="table-cell font-semibold">{{ $subject->name }}</td><td class="table-cell">{{ $subject->schoolClass->name }}</td><td class="table-cell">{{ $subject->teacher?->name ?? 'Any assigned class teacher' }}</td>@if($canManage)<td class="table-cell"><form method="POST" action="{{ route('subjects.destroy', $subject) }}">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form></td>@endif</tr>@empty<tr><td colspan="{{ $canManage ? 4 : 3 }}" class="px-4 py-8 text-center text-sm text-slate-500">No subjects created yet.</td></tr>@endforelse
    </tbody></table></div></section>
</x-app-layout>
