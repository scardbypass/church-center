@extends('layouts.church-center')
@section('title', 'Kelola Gereja — Admin Pusat')
@section('content')
<section class="wrap page-heading" style="text-align:left"><a href="{{ route('admin.index') }}">← Admin Pusat</a>
<h1 style="font-size:clamp(32px,5vw,52px)">Kelola Gereja</h1>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif</section>
<section class="wrap" style="padding-bottom:70px">
@foreach($churches as $church)
<article class="glass" style="padding:20px;border-radius:20px;margin-bottom:12px">
<h3>{{ $church->name }}</h3><p>#{{ $church->id }} · {{ $church->active ? 'Aktif' : 'Dinonaktifkan' }}</p>
<form method="POST" action="{{ route('admin.churches.toggle', $church->id) }}">@csrf @method('PATCH')
<button class="button secondary">{{ $church->active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
</article>
@endforeach
{{ $churches->links() }}
</section>
@endsection
