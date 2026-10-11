<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private function church(Request $request, int $churchId): object
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)
            ->whereIn('role', ['owner', 'fulltime', 'admin'])->exists(), 403);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church && $church->active, 404);
        return $church;
    }

    private function worker(string $method, int $churchId, string $action): array
    {
        $token = config('services.church_worker.token');
        $url = config('services.church_worker.url');
        abort_unless(is_string($token) && strlen($token) >= 32, 503, 'Worker belum dikonfigurasi.');
        abort_unless(is_string($url) && preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~', $url), 503, 'Alamat worker harus loopback.');
        try {
            $response = Http::withToken($token)->timeout(12)->acceptJson()
                ->send($method, $url.'/churches/'.$churchId.'/'.$action);
            if (!$response->successful()) abort(502, 'Worker WhatsApp tidak dapat menyelesaikan permintaan.');
            return $response->json() ?: [];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(503, 'Worker WhatsApp tidak dapat dihubungi.');
        }
    }

    public function index(Request $request, int $churchId)
    {
        $church = $this->church($request, $churchId);
        $status = $this->worker('GET', $churchId, 'status');
        $qrImage = null;
        if (($status['qr_ready'] ?? false) === true) {
            try {
                $qr = $this->worker('GET', $churchId, 'qr');
                $candidate = $qr['image'] ?? null;
                if (is_string($candidate) && preg_match('~^data:image/png;base64,[A-Za-z0-9+/=]+$~', $candidate) && strlen($candidate) < 200000) {
                    $qrImage = $candidate;
                }
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                // QR can expire between status and image fetch.
            }
        }
        return response()->view('church-center.whatsapp', compact('church', 'status', 'qrImage'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function connect(Request $request, int $churchId)
    {
        $this->church($request, $churchId);
        $this->worker('POST', $churchId, 'connect');
        return redirect()->route('churches.whatsapp.index', $churchId)
            ->with('status', 'Permintaan koneksi dikirim. Muat ulang halaman untuk melihat status.');
    }

    public function disconnect(Request $request, int $churchId)
    {
        $this->church($request, $churchId);
        $this->worker('POST', $churchId, 'disconnect');
        return redirect()->route('churches.whatsapp.index', $churchId)
            ->with('status', 'Koneksi dihentikan. Kredensial sesi tetap tersimpan.');
    }
}
