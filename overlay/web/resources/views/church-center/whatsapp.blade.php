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
@if(($status['status'] ?? '') === 'qr_ready' && !empty($qrImage))
<div style="max-width:340px;padding:12px;background:white;border-radius:16px"><img src="{{ $qrImage }}" width="320" height="320" alt="Kode QR untuk menghubungkan WhatsApp gereja" style="max-width:100%;height:auto"></div>
<p>Buka WhatsApp → Perangkat tertaut → Tautkan perangkat, lalu pindai QR ini. QR kedaluwarsa otomatis; muat ulang jika diperlukan.</p>
@elseif(($status['status'] ?? '') === 'qr_ready')
<p>QR sedang disiapkan. Muat ulang halaman.</p>
@elseif(($status['status'] ?? '') === 'connected')
<p>WhatsApp terhubung. Pengiriman otomatis masih dinonaktifkan sampai pemeriksaan izin, lisensi, dan antrean selesai.</p>
@endif
<div style="display:flex;gap:12px;flex-wrap:wrap">
<form method="POST" action="{{ route('churches.whatsapp.connect', $church->id) }}">@csrf<button class="button primary">Hubungkan</button></form>
<form method="POST" action="{{ route('churches.whatsapp.disconnect', $church->id) }}">@csrf<button class="button secondary">Putuskan</button></form>
<a class="button secondary" href="{{ route('churches.whatsapp.index', $church->id) }}">Muat ulang status</a>
</div>
</section>
<section class="wrap glass" style="padding:24px;margin-bottom:28px">
<h2>Balasan Otomatis & FAQ</h2>
<p>Pesan <code>menu</code> atau <code>start</code> menampilkan sambutan. Kata kunci FAQ hanya berlaku di chat pribadi, bukan grup. Satu baris per kata kunci: <code>jadwal|Ibadah Minggu pukul 09.00</code>.</p>
<form method="POST" action="{{ route('churches.whatsapp.replies.update', $church->id) }}" style="display:grid;gap:12px">
@csrf
<label>Pesan sambutan<textarea name="welcome_message" required maxlength="1000" rows="3" style="display:block;width:100%">{{ old('welcome_message', $welcomeMessage) }}</textarea></label>
<label>Kata kunci | jawaban<textarea name="faq" maxlength="10000" rows="7" style="display:block;width:100%" placeholder="jadwal|Ibadah Minggu pukul 09.00">{{ old('faq', $faqText) }}</textarea></label>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<button class="button secondary" type="submit">Simpan dan Sinkronkan Balasan</button>
</form>
</section>
@endsection
