@extends('layouts.church-center')
@section('title', 'Otomatisasi — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('dashboard') }}">← Dashboard</a>
<h1 style="font-size:clamp(32px,5vw,52px)">Jadwal & Otomatisasi</h1>
<p>{{ $church->name }} · Jadwal tersimpan sebagai draft dan tidak akan dikirim sebelum koneksi WhatsApp serta kontrol lisensi selesai.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:28px">
<h2>Buat draft jadwal</h2>
<form method="POST" action="{{ route('churches.automations.store', $church->id) }}" style="display:grid;gap:14px;max-width:650px">
@csrf
<label>Jenis <select name="kind" required style="width:100%;padding:12px">
<option value="ih_morning">IH Pagi</option><option value="ih_noon">IH Siang</option>
<option value="ih_evening">IH Malam</option><option value="broadcast">Broadcast</option>
<option value="reminder">Pengingat</option></select></label>
<label>Tujuan (identitas grup/kontak) <input name="target" required maxlength="120" value="{{ old('target') }}" style="width:100%;padding:12px"></label>
<label>Isi pesan <textarea name="message" required maxlength="5000" rows="6" style="width:100%;padding:12px">{{ old('message') }}</textarea></label>
<label>Waktu (WIB, ISO format) <input name="scheduled_at" required placeholder="2026-10-12 07:00:00" value="{{ old('scheduled_at') }}" style="width:100%;padding:12px"></label>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<button class="button primary" style="border:0">Simpan Draft</button>
</form>
</section>
<section class="wrap" style="padding-bottom:65px">
<h2>Draft tersimpan</h2>
@forelse($jobs as $job)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:12px">
<h3>{{ $job->kind }} · {{ $job->status }}</h3>
<p>Tujuan: {{ $job->target }} · {{ $job->scheduled_at }}</p>
<p style="white-space:pre-wrap">{{ $job->message }}</p>
@if($job->status === 'draft')<form method="POST" action="{{ route('churches.automations.destroy', [$church->id, $job->id]) }}">@csrf @method('DELETE')<button>Hapus draft</button></form>@endif
</article>
@empty<p>Belum ada draft jadwal.</p>@endforelse
{{ $jobs->links() }}
</section>
@endsection
