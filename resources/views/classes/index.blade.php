<x-app-layout>
    <div class="mb-6"><p class="text-sm font-medium text-blue-700">Academic setup</p><h1 class="mt-1 text-2xl font-bold">Classes</h1><p class="mt-1 text-sm text-slate-500">Manage class groups, intake year, and teacher assignments.</p></div>
    @if(auth()->user()->role === 'admin')
        <section class="card mb-6"><h2 class="font-bold">Create a class</h2><form method="POST" action="{{ route('classes.store') }}" class="mt-4 grid gap-4 sm:grid-cols-3">@csrf
            <div><label class="label">Class name</label><input name="name" class="field" placeholder="Form 1, Grade 7..." required></div>
            <div><label class="label">Intake year</label><input type="number" min="2000" max="2100" name="intake_year" value="{{ now()->year }}" class="field" required></div>
            <div><label class="label">Teachers (optional)</label><select name="teacher_ids[]" multiple class="field min-h-11">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
            <div class="sm:col-span-3"><button class="btn-primary">Create class</button></div>
        </form></section>
    @endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($classes as $class)
            <article class="card">
                <div class="flex items-start justify-between"><div><p class="text-sm font-medium text-blue-700">Intake {{ $class->intake_year }}</p><h2 class="mt-1 text-xl font-bold">{{ $class->name }}</h2></div><span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">{{ $class->students_count }} learners</span></div>
                @if(auth()->user()->role === 'admin')
                    <form method="POST" action="{{ route('classes.update', $class) }}" class="mt-5 space-y-3">@csrf @method('PUT')
                        <div><label class="label">Class name</label><input class="field" name="name" value="{{ $class->name }}" required></div>
                        <div><label class="label">Intake year</label><input class="field" type="number" min="2000" max="2100" name="intake_year" value="{{ $class->intake_year }}" required></div>
                        <div><label class="label">Assigned teachers</label><select name="teacher_ids[]" multiple class="field min-h-20">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($class->teachers->contains('id', $teacher->id))>{{ $teacher->name }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">Hold Ctrl / Cmd to select more than one.</p></div>
                        <button class="btn-secondary w-full">Save class and teachers</button>
                    </form>
                    <form method="POST" action="{{ route('classes.destroy', $class) }}" class="mt-2" onsubmit="return confirm('Delete this class? Classes with students cannot be deleted.')">@csrf @method('DELETE')<button class="w-full py-2 text-sm font-semibold text-rose-600">Delete class</button></form>
                @else
                    <div class="mt-4"><p class="label">Teachers</p><p class="text-sm">{{ $class->teachers->pluck('name')->join(', ') ?: 'No teachers assigned' }}</p></div>
                    <a href="{{ route('students.index') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700">View students →</a>
                @endif
            </article>
        @empty
            <div class="card text-sm text-slate-500">No classes have been created yet.</div>
        @endforelse
    </div>
</x-app-layout>
