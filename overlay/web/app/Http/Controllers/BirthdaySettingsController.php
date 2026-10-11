<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthdaySettingsController extends Controller
{
    public function update(Request $request, int $churchId)
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)
            ->whereIn('role', ['owner', 'fulltime', 'admin'])->exists(), 403);
        $data = $request->validate(['message' => ['nullable', 'string', 'max:4500']]);
        $message = trim($data['message'] ?? '');
        DB::table('church_settings')->updateOrInsert(
            ['church_id' => $churchId, 'key' => 'birthday_message'],
            ['value' => json_encode($message), 'created_at' => now(), 'updated_at' => now()]
        );
        return redirect()->route('churches.broadcast.index', $churchId)
            ->with('status', $message ? 'Ucapan ulang tahun otomatis disimpan.' : 'Ucapan ulang tahun otomatis dinonaktifkan.');
    }
}
