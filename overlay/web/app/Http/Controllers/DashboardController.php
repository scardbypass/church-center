<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $churches = DB::table('churches')
            ->join('church_memberships', 'churches.id', '=', 'church_memberships.church_id')
            ->where('church_memberships.user_id', $user->id)
            ->where('church_memberships.active', true)
            ->select('churches.id', 'churches.name', 'churches.slug', 'church_memberships.role')
            ->orderBy('churches.name')->get();

        $counts = [];
        foreach ($churches as $church) {
            $counts[$church->id] = [
                'contacts' => DB::table('church_contacts')->where('church_id', $church->id)->count(),
                'whatsapp' => DB::table('church_whatsapp_connections')->where('church_id', $church->id)->value('status') ?? 'disconnected',
            ];
        }

        return view('church-center.dashboard', compact('user', 'churches', 'counts'));
    }
}
