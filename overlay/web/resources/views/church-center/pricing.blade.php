@extends('layouts.church-center')
@section('title', 'Lisensi — Church Center')
@section('content')
<section class="wrap page-heading"><div class="eyebrow">Lisensi</div><h1>Semua fitur.<br><em>Pilih durasinya.</em></h1><p class="lead">Harga ditentukan Admin Pusat dan belum tersedia pada starter ini. Tidak ada checkout aktif.</p></section>
<section class="wrap grid">
@foreach([1,3,6,12] as $months)
<article class="feature glass"><div class="eyebrow">Lisensi gereja</div><h2>{{ $months }} {{ $months === 1 ? 'Bulan' : 'Bulan' }}</h2><p>Akses ke tier fitur yang sama. Aktivasi dilakukan setelah pembayaran terverifikasi.</p><div class="price">Harga menyusul</div><span class="state">Belum dijual</span></article>
@endforeach
</section>
@endsection
