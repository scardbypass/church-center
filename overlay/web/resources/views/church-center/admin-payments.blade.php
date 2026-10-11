@extends('layouts.church-center')
@section('title', 'Persetujuan Lisensi — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left">
<a href="{{ route('admin.index') }}">← Admin Pusat</a><h1 style="font-size:clamp(32px,5vw,52px)">Permintaan Lisensi</h1>
<p>Hanya setujui permintaan setelah verifikasi pembayaran secara independen. Sistem belum menerima bukti transfer atau webhook QRIS.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap" style="padding-bottom:60px">
@forelse($payments as $payment)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:12px">
<h3>#{{ $payment->id }} · {{ $payment->church_name }}</h3>
<p>{{ $payment->duration_months }} bulan · {{ $payment->status }}</p>
@if($payment->status === 'awaiting_review')
<div style="display:flex;gap:12px;flex-wrap:wrap">
<form method="POST" action="{{ route('admin.payments.approve', $payment->id) }}">@csrf<button class="button primary" onclick="return confirm('Pembayaran sudah diverifikasi di luar sistem?')">Setujui & Aktifkan</button></form>
<form method="POST" action="{{ route('admin.payments.reject', $payment->id) }}">@csrf<button class="button secondary">Tolak</button></form>
</div>
@endif
</article>
@empty<p>Belum ada permintaan.</p>@endforelse
{{ $payments->links() }}
</section>
@endsection
