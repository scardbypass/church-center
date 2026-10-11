@extends('layouts.church-center')
@section('title', 'Pengguna — Admin Pusat')
@section('content')
<section class="wrap page-heading" style="text-align:left"><a href="{{ route('admin.index') }}">← Admin Pusat</a>
<h1>Pengguna</h1><p>Perubahan peran dicatat pada audit log.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap" style="padding-bottom:70px">
@foreach($users as $user)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:12px">
<h3>{{ $user->name }}</h3><p>{{ $user->email }} · {{ $user->platform_role }}</p>
@if($user->id !== auth()->id())
<form method="POST" action="{{ route('admin.users.role', $user->id) }}">@csrf @method('PATCH')
<select name="role"><option value="user" @selected($user->platform_role === 'user')>Pengguna</option><option value="admin" @selected($user->platform_role === 'admin')>Admin Pusat</option></select>
<button class="button secondary" onclick="return confirm('Ubah peran akun ini?')">Simpan Peran</button></form>
@endif
</article>
@endforeach
{{ $users->links() }}
</section>
@endsection
