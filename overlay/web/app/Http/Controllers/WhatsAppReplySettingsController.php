<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WhatsAppReplySettingsController extends Controller
{
    private function authorizeChurch(Request $request, int $churchId): void
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)
            ->whereIn('role', ['owner', 'fulltime', 'admin'])->exists(), 403);
        abort_unless(DB::table('churches')->where('id', $churchId)->where('active', true)->exists(), 404);
    }

    public function update(Request $request, int $churchId)
    {
        $this->authorizeChurch($request, $churchId);
        $data = $request->validate([
            'welcome_message' => ['required', 'string', 'min:2', 'max:1000'],
            'faq' => ['nullable', 'string', 'max:10000'],
        ]);
        $faq = [];
        foreach (preg_split('/\r\n|\n|\r/', trim($data['faq'] ?? '')) as $line) {
            if (trim($line) === '') continue;
            $pair = explode('|', $line, 2);
            if (count($pair) !== 2) return back()->withErrors(['faq' => 'Setiap baris harus berupa kata_kunci|jawaban.']);
            $key = mb_strtolower(trim($pair[0]));
            $answer = trim($pair[1]);
            if (!preg_match('/^[a-z0-9_-]{2,32}$/', $key) || mb_strlen($answer) < 2 || mb_strlen($answer) > 1000) {
                return back()->withErrors(['faq' => 'Kata kunci 2–32 karakter a-z/angka/_/-, jawaban 2–1000 karakter.']);
            }
            $faq[$key] = $answer;
            if (count($faq) > 30) return back()->withErrors(['faq' => 'Maksimal 30 kata kunci.']);
        }
        $settings = ['welcome_message' => trim($data['welcome_message']), 'faq' => $faq];
        DB::table('church_settings')->updateOrInsert(
            ['church_id' => $churchId, 'key' => 'whatsapp_replies'],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]
        );
        $url = config('services.church_worker.url');
        $token = config('services.church_worker.token');
        $synced = false;
        if (is_string($url) && preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~', $url)
            && is_string($token) && strlen($token) >= 32) {
            try {
                $synced = Http::withToken($token)->timeout(5)
                    ->post($url.'/churches/'.$churchId.'/replies', $settings)->successful();
            } catch (\Throwable $e) {
                $synced = false;
            }
        }
        return redirect()->route('churches.whatsapp.index', $churchId)
            ->with('status', $synced ? 'Balasan otomatis tersimpan dan aktif di worker.' :
                'Pengaturan tersimpan, tetapi worker belum tersinkron. Aktifkan worker dan simpan kembali.');
    }
}
