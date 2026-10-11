<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketReplyController extends Controller
{
    public function store(Request $request, int $ticketId)
    {
        $ticket = DB::table('support_tickets')->find($ticketId);
        abort_unless($ticket, 404);
        $admin = $request->user()->platform_role === 'admin';
        abort_unless($admin || DB::table('church_memberships')
            ->where('church_id', $ticket->church_id)->where('user_id', $request->user()->id)
            ->where('active', true)->exists(), 403);
        abort_unless($ticket->status === 'open', 409, 'Tiket telah ditutup.');
        $data = $request->validate(['message' => ['required', 'string', 'min:2', 'max:10000']]);
        DB::table('support_ticket_replies')->insert([
            'ticket_id' => $ticketId, 'user_id' => $request->user()->id,
            'message' => $data['message'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_audit_logs')->insert([
            'church_id' => $ticket->church_id, 'actor_id' => $request->user()->id,
            'action' => 'ticket.reply', 'details' => json_encode(['ticket_id' => $ticketId]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('tickets.show', $ticketId)->with('status', 'Balasan terkirim.');
    }

    public function show(Request $request, int $ticketId)
    {
        $ticket = DB::table('support_tickets')->join('churches', 'churches.id', '=', 'support_tickets.church_id')
            ->where('support_tickets.id', $ticketId)
            ->select('support_tickets.*', 'churches.name as church_name')->first();
        abort_unless($ticket, 404);
        abort_unless($request->user()->platform_role === 'admin' ||
            DB::table('church_memberships')->where('church_id', $ticket->church_id)
                ->where('user_id', $request->user()->id)->where('active', true)->exists(), 403);
        $replies = DB::table('support_ticket_replies')->join('users', 'users.id', '=', 'support_ticket_replies.user_id')
            ->where('ticket_id', $ticketId)->orderBy('support_ticket_replies.id')
            ->select('support_ticket_replies.*', 'users.name as author_name')->get();
        return view('church-center.ticket-detail', compact('ticket', 'replies'));
    }
}
