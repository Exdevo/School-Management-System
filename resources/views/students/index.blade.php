<x-app-layout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-medium text-blue-700">School records</p><h1 class="mt-1 text-2xl font-bold">Students</h1><p class="mt-1 text-sm text-slate-500">Search by learner name, admission number, or parent name.</p></div>
        <form method="GET" class="flex w-full gap-2 sm:w-auto"><input class="field min-w-0 sm:w-72" name="search" value="{{ request('search') }}" placeholder="Name or admission number"><button class="btn-primary">Search</button></form>
    </div>
    @if($canManage)
        <section class="card mb-6">
            <h2 class="font-bold">Add student</h2>
            <form method="POST" action="{{ route('students.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf
                <div><label class="label">Full name</label><input class="field" name="name" value="{{ old('name') }}" required></div>
                <div><label class="label">Admission number</label><input class="field" name="admission_number" value="{{ old('admission_number') }}" required></div>
                <div><label class="label">Class</label><select class="field" name="class_id" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }} · {{ $class->intake_year }}</option>@endforeach</select></div>
                <div><label class="label">Gender</label><select class="field" name="gender" required><option value="">Select</option>@foreach(['Female','Male','Other'] as $gender)<option>{{ $gender }}</option>@endforeach</select></div>
                <div><label class="label">Date of birth</label><input class="field" type="date" name="dob" max="{{ today()->subDay()->toDateString() }}" required></div>
                <div><label class="label">Parent / guardian name</label><input class="field" name="parent_name" required></div>
                <div><label class="label">Parent / guardian phone</label><input class="field" type="tel" name="parent_phone" placeholder="+254 7..." required></div>
                <div><label class="label">Student login email (optional)</label><input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Required to create a login"></div>
                <div><label class="label">Student login phone (optional)</label><input class="field" type="tel" name="phone" value="{{ old('phone') }}"></div>
                <div><label class="label">Login password (required with email)</label><input class="field" type="password" name="password" minlength="8"></div>
                <div><label class="label">Link existing student login</label><select class="field" name="user_id"><option value="">Create record without linking</option>@foreach($unlinkedUsers as $account)<option value="{{ $account->id }}">{{ $account->name }} · {{ $account->email }}</option>@endforeach</select></div>
                <div class="flex items-end sm:col-span-2 lg:col-span-4"><p class="mr-4 flex-1 text-xs text-slate-500">Students may register their own login and the administrator can link it here.</p><button class="btn-primary">Save student</button></div>
            </form>
        </section>
    @endif
    <section class="card overflow-hidden !p-0">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold">Student directory</h2><p class="mt-1 text-sm text-slate-500">{{ $students->total() }} records</p></div></div>
        <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Student</th><th class="px-4 py-3">Admission no.</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Parent / guardian</th><th class="px-4 py-3">DOB</th><th class="px-4 py-3">Reports</th>@if($canManage)<th class="px-4 py-3"></th>@endif</tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($students as $student)
                <tr><td class="table-cell font-semibold">{{ $student->name }}</td><td class="table-cell">{{ $student->admission_number }}</td><td class="table-cell">{{ $student->schoolClass->name }}</td><td class="table-cell"><span class="block">{{ $student->parent_name }}</span><span class="text-xs text-slate-500">{{ $student->parent_phone }}</span></td><td class="table-cell">{{ $student->dob->format('d M Y') }}</td><td class="table-cell"><a class="font-semibold text-blue-700" href="{{ route('results.report', $student) }}">Report card</a></td>
                @if($canManage)<td class="table-cell"><details class="relative"><summary class="cursor-pointer font-semibold text-blue-700">Manage</summary><div class="absolute right-0 z-10 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl sm:w-96"><form method="POST" action="{{ route('students.update', $student) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
                    <div class="sm:col-span-2"><label class="label">Full name</label><input class="field" name="name" value="{{ $student->name }}" required></div>
                    <div><label class="label">Admission number</label><input class="field" name="admission_number" value="{{ $student->admission_number }}" required></div>
                    <div><label class="label">Class</label><select class="field" name="class_id" required>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($student->class_id === $class->id)>{{ $class->name }}</option>@endforeach</select></div>
                    <div><label class="label">Gender</label><select class="field" name="gender">@foreach(['Female','Male','Other'] as $gender)<option @selected($student->gender === $gender)>{{ $gender }}</option>@endforeach</select></div>
                    <div><label class="label">Date of birth</label><input class="field" type="date" name="dob" value="{{ $student->dob->toDateString() }}" required></div>
                    <div><label class="label">Parent / guardian</label><input class="field" name="parent_name" value="{{ $student->parent_name }}" required></div>
                    <div><label class="label">Parent phone</label><input class="field" name="parent_phone" value="{{ $student->parent_phone }}" required></div>
                    <button class="btn-primary sm:col-span-2">Save changes</button>
                </form><form method="POST" action="{{ route('students.destroy', $student) }}" class="mt-2 border-t border-slate-100 pt-2" onsubmit="return confirm('Delete this student and their records? Students with fee history cannot be deleted.')">@csrf @method('DELETE')<button class="w-full py-2 text-sm font-semibold text-rose-600">Delete student</button></form></div></details></td>@endif</tr>
            @empty<tr><td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-sm text-slate-500">No students found. Add your first student above.</td></tr>@endforelse
        </tbody></table></div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $students->links() }}</div>
    </section>
</x-app-layout>
