@extends('layouts.church-center')
@section('title','Monitoring Church Center')
@section('content')
<section class="wrap page-heading"><a href="{{ route('admin.index') }}">← Admin Pusat</a><h1>Monitoring</h1>
<p>Health worker dan jumlah antrean. Bukan monitoring CPU/RAM VPS.</p></section>
<section class="wrap glass" style="padding:24px">
<h2>WhatsApp Worker</h2><p>Status: {{ $worker['status'] ?? 'unavailable' }}</p>
<p>Koneksi aktif: {{ $worker['connected'] ?? 'Tidak diketahui' }}</p>
<p>Uptime (detik): {{ $worker['uptime_seconds'] ?? 'Tidak diketahui' }}</p>
<h2>Antrean</h2>@foreach($stats as $label => $value)<p>{{ $label }}: {{ number_format($value) }}</p>@endforeach
</section>
@endsection
