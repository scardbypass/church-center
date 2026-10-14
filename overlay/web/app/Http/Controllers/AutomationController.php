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
            'target' => ['required', 'string', 'max:120', 'regex:/^[1-9][0-9]{5,15}@s\\.whatsapp\\.net$/'],
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);
        $phone = '+'.explode('@', $data['target'])[0];
        abort_unless(DB::table('church_contacts')->where('church_id', $churchId)
            ->where('phone_e164', $phone)->whereNotNull('consent_at')->exists(), 422,
            'Penerima harus menjadi kontak gereja yang menyetujui pesan.');
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

    public function activate(Request $request, int $churchId, int $jobId)
    {
        $this->authorizeChurch($request, $churchId);
        $licensed = DB::table('church_licenses')->where('church_id', $churchId)
            ->where('status', 'active')->where('expires_at', '>', now())->exists();
        abort_unless($licensed, 403, 'Lisensi gereja belum aktif.');
        $draft = DB::table('church_automation_jobs')->where('church_id', $churchId)
            ->where('id', $jobId)->where('status', 'draft')->first();
        abort_unless($draft, 404);
        $phone = '+'.explode('@', $draft->target)[0];
        abort_unless(DB::table('church_contacts')->where('church_id', $churchId)
            ->where('phone_e164', $phone)->whereNotNull('consent_at')->exists(), 422,
            'Persetujuan kontak sudah tidak berlaku.');
        $updated = DB::table('church_automation_jobs')->where('church_id', $churchId)
            ->where('id', $jobId)->where('status', 'draft')->where('scheduled_at', '>', now())
            ->update(['status' => 'queued', 'updated_at' => now()]);
        abort_unless($updated, 409, 'Draft tidak tersedia atau jadwal sudah lewat.');
        return redirect()->route('churches.automations.index', $churchId)
            ->with('status', 'Jadwal diantrekan. Pengiriman bergantung pada scheduler dan WhatsApp yang terhubung.');
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
