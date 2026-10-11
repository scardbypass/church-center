<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->platform_role === 'admin', 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $users = DB::table('users')->select('id', 'name', 'email', 'platform_role', 'created_at')
            ->orderByDesc('id')->paginate(30);
        return view('church-center.admin-users', compact('users'));
    }

    public function setRole(Request $request, int $userId)
    {
        $this->guard($request);
        $data = $request->validate(['role' => ['required', 'in:admin,user']]);
        abort_if($request->user()->id === $userId, 403, 'Tidak dapat mengubah peran akun sendiri.');
        DB::transaction(function () use ($userId, $data, $request) {
            $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            abort_unless($user, 404);
            if ($user->platform_role === 'admin' && $data['role'] !== 'admin') {
                $admins = DB::table('users')->where('platform_role', 'admin')->count();
                abort_if($admins <= 1, 409, 'Admin terakhir tidak boleh dinonaktifkan.');
            }
            DB::table('users')->where('id', $userId)->update([
                'platform_role' => $data['role'], 'updated_at' => now(),
            ]);
            DB::table('church_audit_logs')->insert([
                'actor_id' => $request->user()->id, 'church_id' => null,
                'action' => 'admin.user.role_changed',
                'details' => json_encode(['user_id' => $userId, 'from' => $user->platform_role, 'to' => $data['role']]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return redirect()->route('admin.users.index')->with('status', 'Peran pengguna diperbarui.');
    }
}
