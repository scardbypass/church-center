<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactConsentController extends Controller
{
    public function revoke(Request $request, int $churchId, int $contactId)
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
        DB::transaction(function () use ($churchId, $contactId, $request) {
            $contact = DB::table('church_contacts')->where('church_id', $churchId)
                ->where('id', $contactId)->lockForUpdate()->first();
            abort_unless($contact, 404);
            DB::table('church_contacts')->where('id', $contactId)->update([
                'consent_at' => null, 'updated_at' => now(),
            ]);
            $jid = ltrim($contact->phone_e164, '+').'@s.whatsapp.net';
            DB::table('church_automation_jobs')->where('church_id', $churchId)
                ->where('target', $jid)->whereIn('status', ['draft', 'queued'])
                ->update(['status' => 'blocked', 'last_error' => 'Consent revoked', 'updated_at' => now()]);
            DB::table('church_recurring_messages')->where('church_id', $churchId)
                ->where('target', $jid)->update(['enabled' => false, 'updated_at' => now()]);
            DB::table('church_audit_logs')->insert([
                'church_id' => $churchId, 'actor_id' => $request->user()->id,
                'action' => 'contacts.consent_revoked',
                'details' => json_encode(['contact_id' => $contactId]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return redirect()->route('churches.contacts.index', $churchId)
            ->with('status', 'Persetujuan dicabut; jadwal yang belum dikirim diblokir.');
    }
}
