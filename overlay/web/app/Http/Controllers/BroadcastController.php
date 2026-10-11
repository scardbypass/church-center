<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BroadcastController extends Controller
{
    private function authorizeChurch(Request $request, int $churchId): object
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)
            ->whereIn('role', ['owner', 'fulltime', 'admin'])->exists(), 403);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church && $church->active, 404);
        return $church;
    }

    public function index(Request $request, int $churchId)
    {
        $church = $this->authorizeChurch($request, $churchId);
        $contacts = DB::table('church_contacts')->where('church_id', $churchId)->whereNotNull('consent_at')->count();
        $jobs = DB::table('church_automation_jobs')->where('church_id', $churchId)
            ->where('kind', 'broadcast')->orderByDesc('id')->paginate(25);
        return view('church-center.broadcast', compact('church', 'contacts', 'jobs'));
    }

    public function store(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);
        abort_unless(DB::table('church_licenses')->where('church_id', $churchId)
            ->where('status', 'active')->where('expires_at', '>', now())->exists(), 403, 'Lisensi belum aktif.');
        $count = DB::table('church_contacts')->where('church_id', $churchId)->whereNotNull('consent_at')->count();
        abort_if($count < 1, 422, 'Tidak ada kontak yang menyetujui pesan.');
        abort_if($count > 1000, 422, 'Maksimal 1000 kontak per batch.');
        $batch = (string) Str::uuid();
        $scheduled = $data['scheduled_at'] ?? now()->toDateTimeString();
        DB::transaction(function () use ($churchId, $data, $batch, $scheduled, $request) {
            DB::table('church_contacts')->where('church_id', $churchId)->whereNotNull('consent_at')
                ->orderBy('id')->chunkById(200, function ($contacts) use ($churchId, $data, $batch, $scheduled) {
                    $rows = [];
                    foreach ($contacts as $contact) {
                        $rows[] = [
                            'church_id' => $churchId, 'kind' => 'broadcast',
                            'target' => ltrim($contact->phone_e164, '+').'@s.whatsapp.net',
                            'message' => $data['message'], 'scheduled_at' => $scheduled,
                            'status' => 'queued', 'idempotency_key' => $batch.':'.$contact->id,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                    DB::table('church_automation_jobs')->insert($rows);
                });
            DB::table('church_audit_logs')->insert([
                'church_id' => $churchId, 'actor_id' => $request->user()->id,
                'action' => 'broadcast.queued',
                'details' => json_encode(['batch' => $batch]), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return redirect()->route('churches.broadcast.index', $churchId)
            ->with('status', "Broadcast diantrekan untuk {$count} kontak. Pengiriman memerlukan scheduler aktif.");
    }
}
