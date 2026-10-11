<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminPaymentsController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->platform_role === 'admin', 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $payments = DB::table('payment_requests')
            ->join('churches', 'churches.id', '=', 'payment_requests.church_id')
            ->select('payment_requests.*', 'churches.name as church_name')
            ->orderByDesc('payment_requests.id')->paginate(30);
        return view('church-center.admin-payments', compact('payments'));
    }

    public function approve(Request $request, int $paymentId)
    {
        $this->guard($request);
        DB::transaction(function () use ($paymentId, $request) {
            $payment = DB::table('payment_requests')->where('id', $paymentId)->lockForUpdate()->first();
            abort_unless($payment, 404);
            abort_unless($payment->status === 'awaiting_review', 409, 'Permintaan telah diproses.');
            abort_unless(in_array((int) $payment->duration_months, [1,3,6,12], true), 422);
            // Serialize concurrent approvals for the same church.
            $church = DB::table('churches')->where('id', $payment->church_id)->lockForUpdate()->first();
            abort_unless($church, 404);
            $latest = DB::table('church_licenses')->where('church_id', $church->id)
                ->where('status', 'active')->orderByDesc('expires_at')->first();
            $now = now();
            $start = $latest && $latest->expires_at && Carbon::parse($latest->expires_at)->gt($now)
                ? Carbon::parse($latest->expires_at) : $now;
            $expires = $start->copy()->addMonthsNoOverflow((int) $payment->duration_months);
            DB::table('church_licenses')->insert([
                'church_id' => $church->id, 'duration_months' => $payment->duration_months,
                'status' => 'active', 'starts_at' => $now, 'expires_at' => $expires,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('payment_requests')->where('id', $paymentId)->update([
                'status' => 'approved', 'reviewed_at' => $now, 'reviewed_by' => $request->user()->id,
                'updated_at' => $now,
            ]);
        }, 3);
        return redirect()->route('admin.payments.index')->with('status', 'Lisensi diaktifkan berdasarkan persetujuan Admin.');
    }

    public function reject(Request $request, int $paymentId)
    {
        $this->guard($request);
        $updated = DB::table('payment_requests')->where('id', $paymentId)
            ->where('status', 'awaiting_review')->update([
                'status' => 'rejected', 'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id, 'updated_at' => now(),
            ]);
        abort_unless($updated, 409, 'Permintaan telah diproses.');
        return redirect()->route('admin.payments.index')->with('status', 'Permintaan ditolak.');
    }
}
