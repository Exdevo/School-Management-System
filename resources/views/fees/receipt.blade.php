<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#172554;font-size:12px}.wrap{max-width:680px;margin:20px auto;border:1px solid #dbeafe;padding:32px}.top{border-bottom:2px solid #1d4ed8;padding-bottom:18px}.school{font-size:21px;font-weight:bold;color:#1e3a8a}.muted{color:#64748b}.row{padding:10px 0;border-bottom:1px solid #e2e8f0}.label{display:inline-block;width:180px;color:#64748b}.amount{font-size:22px;font-weight:bold;color:#1d4ed8}.footer{margin-top:24px;color:#64748b}
</style></head><body><div class="wrap">
    <div class="top"><div class="school">{{ $schoolName }}</div><p class="muted">OFFICIAL FEE PAYMENT RECEIPT</p><p>Receipt #{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</p></div>
    <div class="row"><span class="label">Student</span><strong>{{ $payment->student->name }}</strong></div>
    <div class="row"><span class="label">Admission number</span>{{ $payment->student->admission_number }}</div>
    <div class="row"><span class="label">Class</span>{{ $payment->student->schoolClass->name }}</div>
    <div class="row"><span class="label">Term / academic year</span>{{ $payment->term }} {{ $payment->academic_year }}</div>
    <div class="row"><span class="label">Amount received</span><span class="amount">KSh {{ number_format((float) $payment->amount, 2) }}</span></div>
    <div class="row"><span class="label">Payment method</span>{{ $payment->payment_method }}{{ $payment->mpesa_code ? ' · '.$payment->mpesa_code : '' }}</div>
    <div class="row"><span class="label">Date received</span>{{ $payment->paid_at->format('d M Y') }}</div>
    <div class="row"><span class="label">Remaining term balance</span><strong>KSh {{ number_format($balance, 2) }}</strong></div>
    <p class="footer">Thank you. Keep this receipt for your records.</p>
</div></body></html>
