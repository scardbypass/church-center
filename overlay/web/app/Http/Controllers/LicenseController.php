<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    private function member(Request $request, int $churchId): void
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
    }

    public function index(Request $request, int $churchId)
    {
        $this->member($request, $churchId);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church, 404);
        $licenses = DB::table('church_licenses')->where('church_id', $churchId)->orderByDesc('id')->get();
        $payments = DB::table('payment_requests')->where('church_id', $churchId)->orderByDesc('id')->get();
        return view('church-center.licenses', compact('church', 'licenses', 'payments'));
    }

    public function request(Request $request, int $churchId)
    {
        $this->member($request, $churchId);
        $data = $request->validate(['duration_months' => ['required', 'integer', Rule::in([1, 3, 6, 12])]]);
        DB::table('payment_requests')->insert([
            'church_id' => $churchId,
            'user_id' => $request->user()->id,
            'duration_months' => (int) $data['duration_months'],
            'status' => 'awaiting_review',
            'provider' => 'manual',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('churches.licenses.index', $churchId)
            ->with('status', 'Permintaan dibuat. Belum ada pembayaran yang dikonfirmasi atau lisensi yang diaktifkan.');
    }
}
