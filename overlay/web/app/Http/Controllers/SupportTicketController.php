<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    private function allowed(Request $request, int $churchId): void
    {
        abort_unless(DB::table('church_memberships')->where('church_id', $churchId)
            ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
    }

    public function index(Request $request, int $churchId)
    {
        $this->allowed($request, $churchId);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church, 404);
        $tickets = DB::table('support_tickets')->where('church_id', $churchId)
            ->orderByDesc('id')->paginate(20);
        return view('church-center.tickets', compact('church', 'tickets'));
    }

    public function store(Request $request, int $churchId)
    {
        $this->allowed($request, $churchId);
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
        ]);
        DB::table('support_tickets')->insert([
            'church_id' => $churchId, 'user_id' => $request->user()->id,
            'subject' => $data['subject'], 'message' => $data['message'],
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('churches.tickets.index', $churchId)->with('status', 'Tiket berhasil dikirim.');
    }
}
