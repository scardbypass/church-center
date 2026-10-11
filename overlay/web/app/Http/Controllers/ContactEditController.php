<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactEditController extends Controller
{
    public function update(Request $request, int $churchId, int $contactId)
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $affected = DB::table('church_contacts')->where('church_id', $churchId)
            ->where('id', $contactId)->update([
                'name' => $data['name'], 'birthday' => $data['birthday'] ?? null,
                'updated_at' => now(),
            ]);
        abort_unless($affected, 404);
        return redirect()->route('churches.contacts.index', $churchId)->with('status', 'Kontak diperbarui.');
    }
}
