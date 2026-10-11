<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactImportController extends Controller
{
    public function store(Request $request, int $churchId)
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        abort_unless($handle, 422, 'File tidak dapat dibaca.');
        try {
            $header = fgetcsv($handle);
            abort_unless($header, 422, 'CSV kosong.');
            $header = array_map(fn ($s) => strtolower(trim((string) $s)), $header);
            abort_unless(in_array('name', $header) && in_array('phone', $header), 422, 'Kolom name dan phone wajib ada.');
            $inserted = 0; $skipped = 0; $rows = 0;
            while (($fields = fgetcsv($handle)) !== false) {
                $rows++;
                abort_if($rows > 5000, 422, 'Maksimal 5000 baris per impor.');
                if (count($fields) !== count($header)) { $skipped++; continue; }
                $row = array_combine($header, $fields);
                $name = trim((string) ($row['name'] ?? ''));
                $digits = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
                if (str_starts_with($digits, '0')) $digits = '62'.substr($digits, 1);
                elseif (str_starts_with($digits, '8')) $digits = '62'.$digits;
                if (mb_strlen($name) < 2 || mb_strlen($name) > 255 || !preg_match('/^62[0-9]{8,13}$/', $digits)) {
                    $skipped++; continue;
                }
                // Imported contacts are NEVER opted in based on an unverified CSV column.
                $affected = DB::table('church_contacts')->insertOrIgnore([
                    'church_id' => $churchId, 'name' => $name,
                    'phone_e164' => '+'.$digits, 'birthday' => null, 'consent_at' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $inserted += $affected; $skipped += 1 - $affected;
            }
            DB::table('church_audit_logs')->insert([
                'church_id' => $churchId, 'actor_id' => $request->user()->id,
                'action' => 'contacts.csv_import',
                'details' => json_encode(['inserted' => $inserted, 'skipped' => $skipped]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } finally {
            fclose($handle);
        }
        return redirect()->route('churches.contacts.index', $churchId)
            ->with('status', "Impor selesai: {$inserted} kontak baru, {$skipped} dilewati. Persetujuan pesan harus diperoleh terpisah.");
    }
}
