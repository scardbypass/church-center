@extends('layouts.church-center')
@section('title', 'Admin Pusat — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('dashboard') }}">← Dashboard</a>
<h1 style="font-size:clamp(32px,5vw,52px)">Admin Pusat</h1>
<p>Ringkasan operasional dan tiket masuk.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap grid">
<article class="feature glass"><h2>{{ $churchCount }}</h2><p>Gereja</p></article>
<article class="feature glass"><h2>{{ $userCount }}</h2><p>Pengguna</p></article>
<article class="feature glass"><h2>{{ $ticketCount }}</h2><p>Tiket terbuka</p></article>
<article class="feature glass"><h2>{{ $pendingPayments }}</h2><p>Permintaan pembayaran menunggu</p></article>
</section>
<section class="wrap" style="padding:35px 0 70px">
<h2>Tiket terbaru</h2>
@forelse($tickets as $ticket)
<article class="glass" style="padding:22px;border-radius:20px;margin-bottom:12px">
<h3>{{ $ticket->subject }}</h3>
<p>{{ $ticket->church_name }} · {{ $ticket->status }}</p>
<p style="white-space:pre-wrap">{{ $ticket->message }}</p>
@if($ticket->status === 'open')
<form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}">@csrf @method('PATCH')<button class="button secondary">Tutup Tiket</button></form>
@endif
</article>
@empty<p>Belum ada tiket.</p>@endforelse
</section>
@endsection
