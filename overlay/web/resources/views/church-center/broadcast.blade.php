@extends('layouts.church-center')
@section('title', 'Broadcast — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left"><a href="{{ route('dashboard') }}">← Dashboard</a>
<h1>Broadcast WhatsApp</h1><p>{{ $church->name }} · {{ $contacts }} kontak dengan persetujuan.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:25px">
<h2>Antrekan broadcast</h2><p>Hanya dikirim ke kontak yang menyetujui pesan. Maksimal 1000 penerima per batch.</p>
<form method="POST" action="{{ route('churches.broadcast.store', $church->id) }}">@csrf
<label>Pesan <textarea name="message" required minlength="2" maxlength="5000" rows="6" style="width:100%">{{ old('message') }}</textarea></label>
<label>Jadwal (kosongkan untuk sekarang) <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"></label>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<button class="button primary" onclick="return confirm('Antrekan broadcast untuk semua kontak yang telah menyetujui?')">Antrekan Broadcast</button></form></section>
<section class="wrap" style="padding-bottom:70px"><h2>Riwayat per penerima</h2>
@foreach($jobs as $job)<article class="glass" style="padding:16px;border-radius:16px;margin-bottom:10px">
<p>{{ $job->target }} · {{ $job->status }} · {{ $job->scheduled_at }}</p>
@if($job->last_error)<p>{{ $job->last_error }}</p>@endif</article>@endforeach
{{ $jobs->links() }}</section>
@endsection
