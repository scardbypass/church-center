@extends('layouts.church-center')
@section('title', 'Auto IH Harian — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left"><a href="{{ route('dashboard') }}">← Dashboard</a><h1>Auto IH Harian</h1>
<p>{{ $church->name }} · Zona waktu: {{ $church->timezone }}. Setiap jadwal harus diaktifkan sendiri. Hanya nomor kontak yang telah menyetujui pesan.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:28px">
<form method="POST" action="{{ route('churches.recurring.store', $church->id) }}" style="display:grid;gap:12px;max-width:640px">@csrf
<label>Jenis <select name="kind" required><option value="ih_morning">IH Pagi</option><option value="ih_noon">IH Siang</option><option value="ih_evening">IH Malam</option><option value="reminder">Pengingat</option></select></label>
<label>Nomor WhatsApp (628xxx@s.whatsapp.net) <input name="target" required value="{{ old('target') }}"></label>
<label>Jam lokal (HH:MM) <input type="time" name="local_time" required value="{{ old('local_time', '07:00') }}"></label>
<label>Pesan <textarea name="message" required maxlength="5000">{{ old('message') }}</textarea></label>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<button class="button primary">Simpan (nonaktif)</button></form>
</section>
<section class="wrap" style="padding-bottom:60px">
@forelse($rules as $rule)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:12px">
<h3>{{ $rule->kind }} · {{ $rule->local_time }} · {{ $rule->enabled ? 'Aktif' : 'Nonaktif' }}</h3>
<p>{{ $rule->target }}</p><p style="white-space:pre-wrap">{{ $rule->message }}</p>
<form method="POST" action="{{ route('churches.recurring.toggle', [$church->id, $rule->id]) }}">@csrf @method('PATCH')<button class="button secondary">{{ $rule->enabled ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
<form method="POST" action="{{ route('churches.recurring.destroy', [$church->id, $rule->id]) }}">@csrf @method('DELETE')<button class="button secondary">Hapus</button></form>
</article>
@empty<p>Belum ada jadwal harian.</p>@endforelse
</section>
@endsection
