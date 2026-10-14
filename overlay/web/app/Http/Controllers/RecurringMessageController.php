<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecurringMessageController extends Controller
{
    private function church(Request $request, int $id): object
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $id)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
        $church = DB::table('churches')->find($id);
        abort_unless($church && $church->active, 404);
        return $church;
    }
    public function index(Request $request, int $churchId)
    {
        $church = $this->church($request, $churchId);
        $rules = DB::table('church_recurring_messages')->where('church_id', $churchId)->orderBy('id')->get();
        return view('church-center.recurring', compact('church', 'rules'));
    }
    public function store(Request $request, int $churchId)
    {
        $church = $this->church($request, $churchId);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['ih_morning', 'ih_noon', 'ih_evening', 'reminder'])],
            'target' => ['required', 'regex:/^[1-9][0-9]{5,15}@s\\.whatsapp\\.net$/'],
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'local_time' => ['required', 'date_format:H:i'],
        ]);
        $phone = '+'.explode('@', $data['target'])[0];
        abort_unless(DB::table('church_contacts')->where('church_id', $churchId)
            ->where('phone_e164', $phone)->whereNotNull('consent_at')->exists(), 422,
            'Kontak harus ada dan telah menyetujui pengiriman.');
        DB::table('church_recurring_messages')->insert([
            'church_id' => $churchId, 'kind' => $data['kind'],
            'target' => $data['target'], 'message' => $data['message'],
            'local_time' => $data['local_time'], 'timezone' => $church->timezone,
            'enabled' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('churches.recurring.index', $churchId)->with('status', 'Jadwal dibuat dalam keadaan nonaktif.');
    }
    public function toggle(Request $request, int $churchId, int $ruleId)
    {
        $this->church($request, $churchId);
        $rule = DB::table('church_recurring_messages')->where('church_id', $churchId)->find($ruleId);
        abort_unless($rule, 404);
        if (!$rule->enabled) {
            $licensed = DB::table('church_licenses')->where('church_id', $churchId)
                ->where('status', 'active')->where('expires_at', '>', now())->exists();
            abort_unless($licensed, 403, 'Lisensi gereja belum aktif.');
            $targetPhone = '+'.explode('@', $rule->target)[0];
            abort_unless(DB::table('church_contacts')->where('church_id', $churchId)
                ->where('phone_e164', $targetPhone)->whereNotNull('consent_at')->exists(),
                422, 'Persetujuan kontak tidak ada atau sudah dicabut.');
        }
        DB::table('church_recurring_messages')->where('church_id', $churchId)
            ->where('id', $ruleId)->update([
                'enabled' => !$rule->enabled, 'updated_at' => now(),
            ]);
        return redirect()->route('churches.recurring.index', $churchId);
    }
    public function destroy(Request $request, int $churchId, int $ruleId)
    {
        $this->church($request, $churchId);
        abort_unless(DB::table('church_recurring_messages')->where('church_id', $churchId)
            ->where('id', $ruleId)->delete(), 404);
        return redirect()->route('churches.recurring.index', $churchId);
    }
}
