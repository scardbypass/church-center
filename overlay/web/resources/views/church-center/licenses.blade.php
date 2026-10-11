@extends('layouts.church-center')
@section('title', 'Lisensi Gereja — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('dashboard') }}">← Dashboard</a><h1 style="font-size:clamp(32px,5vw,52px)">Lisensi {{ $church->name }}</h1>
<p>Permintaan manual menunggu pemeriksaan Admin. Jangan transfer sebelum menerima instruksi resmi yang terverifikasi.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:24px;border-radius:24px;margin-bottom:28px">
<h2>Ajukan lisensi</h2>
<form method="POST" action="{{ route('churches.licenses.request', $church->id) }}">
@csrf
<label>Durasi <select name="duration_months" required><option value="1">1 bulan</option><option value="3">3 bulan</option><option value="6">6 bulan</option><option value="12">12 bulan</option></select></label>
<button class="button primary" style="border:0">Ajukan untuk ditinjau</button>
</form>
</section>
<section class="wrap" style="padding-bottom:60px">
<h2>Riwayat lisensi</h2>
@forelse($licenses as $license)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:10px">{{ $license->duration_months }} bulan · {{ $license->status }} · Berakhir: {{ $license->expires_at }}</article>
@empty<p>Belum ada lisensi.</p>@endforelse
<h2>Permintaan</h2>
@forelse($payments as $payment)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:10px">#{{ $payment->id }} · {{ $payment->duration_months }} bulan · {{ $payment->status }}</article>
@empty<p>Belum ada permintaan.</p>@endforelse
</section>
@endsection
