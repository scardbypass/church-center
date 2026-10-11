@extends('layouts.church-center')
@section('title', 'WhatsApp — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('dashboard') }}">← Dashboard</a>
<h1 style="font-size:clamp(32px,5vw,52px)">WhatsApp Gereja</h1>
<p>{{ $church->name }} · Status: <strong>{{ $status['status'] ?? 'unknown' }}</strong></p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:60px">
<p>Gunakan nomor WhatsApp yang dikelola gereja. QR adalah kredensial login: jangan dibagikan kepada orang lain.</p>
@if(($status['status'] ?? '') === 'qr_ready' && !empty($status['qr']))
<p>QR tersedia di worker. Tampilan kode QR yang dapat dipindai belum diintegrasikan; jangan menyalin token QR ke layanan pembuat QR publik.</p>
@elseif(($status['status'] ?? '') === 'connected')
<p>WhatsApp terhubung. Pengiriman otomatis masih dinonaktifkan sampai pemeriksaan izin, lisensi, dan antrean selesai.</p>
@endif
<div style="display:flex;gap:12px;flex-wrap:wrap">
<form method="POST" action="{{ route('churches.whatsapp.connect', $church->id) }}">@csrf<button class="button primary">Hubungkan</button></form>
<form method="POST" action="{{ route('churches.whatsapp.disconnect', $church->id) }}">@csrf<button class="button secondary">Putuskan</button></form>
<a class="button secondary" href="{{ route('churches.whatsapp.index', $church->id) }}">Muat ulang status</a>
</div>
</section>
@endsection
