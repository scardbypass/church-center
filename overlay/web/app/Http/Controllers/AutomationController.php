<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    private function authorizeChurch(Request $request, int $churchId): void
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
    }

    public function index(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church, 404);
        $jobs = DB::table('church_automation_jobs')->where('church_id', $churchId)
            ->orderByDesc('scheduled_at')->paginate(20);
        return view('church-center.automations', compact('church', 'jobs'));
    }

    public function store(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['ih_morning', 'ih_noon', 'ih_evening', 'broadcast', 'reminder'])],
            'target' => ['required', 'string', 'min:3', 'max:120'],
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);
        DB::table('church_automation_jobs')->insert([
            'church_id' => $churchId,
            'kind' => $data['kind'],
            'target' => $data['target'],
            'message' => $data['message'],
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'draft',
            'idempotency_key' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('churches.automations.index', $churchId)
            ->with('status', 'Draft jadwal tersimpan. Pengiriman belum aktif hingga worker WhatsApp terhubung.');
    }

    public function destroy(Request $request, int $churchId, int $jobId)
    {
        $this->authorizeChurch($request, $churchId);
        $deleted = DB::table('church_automation_jobs')->where('church_id', $churchId)
            ->where('id', $jobId)->where('status', 'draft')->delete();
        abort_unless($deleted, 404);
        return redirect()->route('churches.automations.index', $churchId)
            ->with('status', 'Draft dihapus.');
    }
}
