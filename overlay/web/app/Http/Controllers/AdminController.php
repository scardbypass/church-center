<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->platform_role === 'admin', 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        return view('church-center.admin', [
            'churchCount' => DB::table('churches')->count(),
            'userCount' => DB::table('users')->count(),
            'ticketCount' => DB::table('support_tickets')->where('status', 'open')->count(),
            'pendingPayments' => DB::table('payment_requests')->where('status', 'awaiting_review')->count(),
            'tickets' => DB::table('support_tickets')->join('churches', 'churches.id', '=', 'support_tickets.church_id')
                ->select('support_tickets.*', 'churches.name as church_name')->orderByDesc('support_tickets.id')->limit(30)->get(),
        ]);
    }

    public function closeTicket(Request $request, int $ticketId)
    {
        $this->guard($request);
        $updated = DB::table('support_tickets')->where('id', $ticketId)->where('status', 'open')
            ->update(['status' => 'closed', 'updated_at' => now()]);
        abort_unless($updated, 404);
        return redirect()->route('admin.index')->with('status', 'Tiket ditutup.');
    }
}
