<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminChurchesController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->platform_role === 'admin', 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $churches = DB::table('churches')->orderByDesc('id')->paginate(30);
        return view('church-center.admin-churches', compact('churches'));
    }

    public function toggle(Request $request, int $churchId)
    {
        $this->guard($request);
        $church = DB::table('churches')->find($churchId);
        abort_unless($church, 404);
        DB::table('churches')->where('id', $churchId)->update([
            'active' => !$church->active, 'updated_at' => now(),
        ]);
        DB::table('church_audit_logs')->insert([
            'church_id' => $churchId, 'actor_id' => $request->user()->id,
            'action' => 'admin.church.toggle',
            'details' => json_encode(['active' => !$church->active]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('admin.churches.index')->with('status', 'Status gereja diperbarui.');
    }
}
