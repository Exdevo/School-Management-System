<x-app-layout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-medium text-blue-700">Finance</p><h1 class="mt-1 text-2xl font-bold">Fee management</h1><p class="mt-1 text-sm text-slate-500">Set term fees, record payments, track balances and print receipts.</p></div>
        <form method="GET" class="flex gap-2"><select class="field w-auto" name="term">@foreach(['Term 1','Term 2','Term 3'] as $option)<option @selected($term === $option)>{{ $option }}</option>@endforeach</select><input class="field w-24" name="year" type="number" value="{{ $year }}"><button class="btn-secondary">Filter</button></form>
    </div>
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="card"><h2 class="font-bold">Set class fee structure</h2><form method="POST" action="{{ route('fees.structures.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
            <div class="sm:col-span-2"><label class="label">Class</label><select class="field" name="class_id" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} · {{ $class->intake_year }}</option>@endforeach</select></div>
            <div><label class="label">Term</label><select class="field" name="term">@foreach(['Term 1','Term 2','Term 3'] as $option)<option @selected($term === $option)>{{ $option }}</option>@endforeach</select></div>
            <div><label class="label">Academic year</label><input class="field" type="number" name="academic_year" value="{{ $year }}" min="2000" max="2100"></div>
            <div><label class="label">Total fee (KSh)</label><input class="field" type="number" name="amount" min="0.01" step="0.01" required></div>
            <div class="flex items-end"><button class="btn-primary w-full">Save fee structure</button></div>
        </form></section>
        <section class="card"><h2 class="font-bold">Record payment</h2><form method="POST" action="{{ route('fees.payments.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
            <div class="sm:col-span-2"><label class="label">Student</label><select class="field" name="student_id" required><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->admission_number }} ({{ $student->schoolClass->name }})</option>@endforeach</select></div>
            <div><label class="label">Amount (KSh)</label><input class="field" type="number" name="amount" min="0.01" step="0.01" required></div>
            <div><label class="label">Payment date</label><input class="field" type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required></div>
            <div><label class="label">Term</label><select class="field" name="term">@foreach(['Term 1','Term 2','Term 3'] as $option)<option @selected($term === $option)>{{ $option }}</option>@endforeach</select></div>
            <div><label class="label">Academic year</label><input class="field" type="number" name="academic_year" value="{{ $year }}" min="2000" max="2100" required></div>
            <div><label class="label">Payment method</label><select class="field" name="payment_method" required><option>M-Pesa</option><option>Cash</option><option>Bank</option></select></div>
            <div><label class="label">M-Pesa transaction code</label><input class="field uppercase" name="mpesa_code" maxlength="30" placeholder="Required for M-Pesa"></div>
            <div class="sm:col-span-2"><button class="btn-primary w-full">Record payment & generate receipt</button></div>
        </form></section>
    </div>
    <section class="card mt-5 overflow-hidden !p-0">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Fee structures · {{ $term }} {{ $year }}</h2></div>
        <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Class</th><th class="px-4 py-3">Term fee</th><th class="px-4 py-3">Academic year</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($structures as $structure)<tr><td class="table-cell font-semibold">{{ $structure->schoolClass->name }}</td><td class="table-cell">KSh {{ number_format((float) $structure->amount, 2) }}</td><td class="table-cell">{{ $structure->academic_year }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-7 text-center text-sm text-slate-500">No fee structures set for this term.</td></tr>@endforelse</tbody></table></div>
    </section>
    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="card overflow-hidden !p-0"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Defaulters</h2><p class="mt-1 text-sm text-slate-500">Students with an outstanding balance for {{ $term }} {{ $year }}</p></div>
            <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Student</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Balance</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($defaulters as $student)<tr><td class="table-cell font-semibold">{{ $student->name }}<span class="block text-xs text-slate-500">{{ $student->admission_number }}</span></td><td class="table-cell">{{ $student->schoolClass->name }}</td><td class="table-cell font-semibold text-rose-700">KSh {{ number_format($balanceFor($student), 2) }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-7 text-center text-sm text-slate-500">No defaulters. Great work!</td></tr>@endforelse</tbody></table></div>
        </section>
        <section class="card overflow-hidden !p-0"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Recent payments</h2></div>
            <div class="overflow-x-auto"><table class="w-full"><thead><tr class="table-head"><th class="px-4 py-3">Student</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Method</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($payments as $payment)<tr><td class="table-cell font-semibold">{{ $payment->student->name }}<span class="block text-xs text-slate-500">{{ $payment->paid_at->format('d M Y') }}</span></td><td class="table-cell">KSh {{ number_format((float) $payment->amount, 2) }}</td><td class="table-cell">{{ $payment->payment_method }}</td><td class="table-cell"><a class="font-semibold text-blue-700" href="{{ route('fees.receipt', $payment) }}">Receipt PDF</a></td></tr>@empty<tr><td colspan="4" class="px-4 py-7 text-center text-sm text-slate-500">No payments found.</td></tr>@endforelse</tbody></table></div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $payments->links() }}</div>
        </section>
    </div>
</x-app-layout>
