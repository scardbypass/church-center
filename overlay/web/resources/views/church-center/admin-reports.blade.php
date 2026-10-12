@extends('layouts.church-center')
@section('title','Laporan Admin Pusat')
@section('content')
<section class="wrap page-heading"><a href="{{ route('admin.index') }}">← Admin Pusat</a><h1>Laporan Sistem</h1><p>Angka pesan merupakan status antrean, bukan bukti pesan diterima penerima.</p></section>
<section class="wrap glass" style="padding:24px;margin-bottom:20px"><h2>Status pengiriman</h2>
@foreach($counts as $status => $total)<p>{{ $status }}: {{ number_format($total) }}</p>@endforeach
<h2>Pembayaran</h2>@foreach($payments as $payment)
<p>{{ $payment->status }}: {{ $payment->total }} transaksi · Rp{{ number_format($payment->amount,0,',','.') }}</p>
@endforeach
<a class="button secondary" href="{{ route('admin.reports.export') }}">Ekspor CSV gereja</a></section>
<section class="wrap"><h2>Gereja</h2>@foreach($churches as $church)
<article class="glass" style="padding:15px;margin-bottom:8px">{{ $church->name }} · {{ $church->active ? 'Aktif' : 'Nonaktif' }}</article>
@endforeach{{ $churches->links() }}</section>
@endsection
