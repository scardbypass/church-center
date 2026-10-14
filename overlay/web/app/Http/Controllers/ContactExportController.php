<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactExportController extends Controller
{
    public function download(Request $request, int $churchId)
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)
            ->whereIn('role', ['owner', 'fulltime', 'admin'])->exists(), 403);
        abort_unless(DB::table('churches')->where('id', $churchId)->exists(), 404);
        return response()->streamDownload(function () use ($churchId) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['name', 'phone', 'birthday', 'consent_status']);
            DB::table('church_contacts')->where('church_id', $churchId)->orderBy('id')
                ->chunkById(200, function ($contacts) use ($stream) {
                    foreach ($contacts as $contact) {
                        $name = (string) $contact->name;
                        if (preg_match('/^\s*[=+@\-]/u', $name)) $name = "'".$name;
                        fputcsv($stream, [
                            $name, $contact->phone_e164, $contact->birthday,
                            $contact->consent_at ? 'consented' : 'not_consented',
                        ]);
                    }
                });
            fclose($stream);
        }, 'church-contacts-'.$churchId.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
