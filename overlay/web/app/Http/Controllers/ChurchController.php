<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChurchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ]);
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId) {
            $slug = Str::slug($data['name']).'-'.Str::lower(Str::random(8));
            $churchId = DB::table('churches')->insertGetId([
                'name' => $data['name'],
                'slug' => $slug,
                'timezone' => 'Asia/Jakarta',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('church_memberships')->insert([
                'church_id' => $churchId,
                'user_id' => $userId,
                'role' => 'owner',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('dashboard')->with('status', 'Gereja berhasil dibuat.');
    }
}
