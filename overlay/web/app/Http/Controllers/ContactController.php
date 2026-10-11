<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    private function authorizeChurch(Request $request, int $churchId): void
    {
        abort_unless(
            DB::table('church_memberships')
                ->where('church_id', $churchId)
                ->where('user_id', $request->user()->id)
                ->where('active', true)
                ->exists(),
            403,
            'Anda tidak memiliki akses ke gereja ini.'
        );
    }

    public function index(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $church = DB::table('churches')->where('id', $churchId)->first();
        abort_unless($church, 404);
        $contacts = DB::table('church_contacts')
            ->where('church_id', $churchId)
            ->orderBy('name')
            ->paginate(25);

        return view('church-center.contacts', compact('church', 'contacts'));
    }

    public function store(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'consent' => ['nullable', 'accepted'],
        ]);
        $digits = preg_replace('/\D+/', '', $data['phone']);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }
        if (! preg_match('/^62[0-9]{8,13}$/', $digits)) {
            return back()->withErrors(['phone' => 'Nomor Indonesia tidak valid.'])->withInput();
        }
        $phone = '+'.$digits;
        if (DB::table('church_contacts')->where('church_id', $churchId)->where('phone_e164', $phone)->exists()) {
            return back()->withErrors(['phone' => 'Nomor sudah ada di gereja ini.'])->withInput();
        }
        DB::table('church_contacts')->insert([
            'church_id' => $churchId,
            'name' => $data['name'],
            'phone_e164' => $phone,
            'birthday' => $data['birthday'] ?? null,
            'consent_at' => ! empty($data['consent']) ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('churches.contacts.index', $churchId)->with('status', 'Kontak tersimpan.');
    }

    public function destroy(Request $request, int $churchId, int $contactId)
    {
        $this->authorizeChurch($request, $churchId);
        $deleted = DB::table('church_contacts')
            ->where('church_id', $churchId)
            ->where('id', $contactId)
            ->delete();
        abort_unless($deleted, 404);
        return redirect()->route('churches.contacts.index', $churchId)->with('status', 'Kontak dihapus.');
    }
}
