@extends('layouts.church-center')
@section('title', 'Bantuan — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('dashboard') }}">← Dashboard</a>
<h1 style="font-size:clamp(32px,5vw,52px)">Tiket Bantuan</h1>
<p>{{ $church->name }}</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:30px">
<h2>Kirim Tiket Baru</h2>
<form method="POST" action="{{ route('churches.tickets.store', $church->id) }}" style="display:grid;gap:14px;max-width:650px">
@csrf
<label>Judul <input name="subject" required maxlength="160" value="{{ old('subject') }}" style="width:100%;padding:12px"></label>
<label>Pesan <textarea name="message" required minlength="10" maxlength="10000" rows="5" style="width:100%;padding:12px">{{ old('message') }}</textarea></label>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<button class="button primary" style="border:0">Kirim Tiket</button>
</form>
</section>
<section class="wrap" style="padding-bottom:60px">
@forelse($tickets as $ticket)
<article class="glass" style="padding:22px;border-radius:20px;margin-bottom:12px">
<h3><a href="{{ route('tickets.show', $ticket->id) }}">{{ $ticket->subject }} →</a></h3>
<p>Status: {{ $ticket->status }} · {{ $ticket->created_at }}</p>
<p style="white-space:pre-wrap">{{ $ticket->message }}</p>
</article>
@empty
<p>Belum ada tiket.</p>
@endforelse
{{ $tickets->links() }}
</section>
@endsection
