@extends('layouts.church-center')
@section('title', 'Dashboard Fulltime — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left;padding-bottom:35px">
  <div class="eyebrow">Dashboard Fulltime</div>
  <h1 style="font-size:clamp(32px,5vw,54px)">Selamat datang,<br><em>{{ $user->name }}</em></h1>
  <p class="lead" style="margin:0">Kelola gereja yang terhubung dengan akun Anda. Data setiap gereja terpisah.</p>
  @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap grid">
@forelse($churches as $church)
<article class="feature glass">
 <span class="emoji">⛪</span>
 <h2>{{ $church->name }}</h2>
 <p>Peran: {{ ucfirst($church->role) }}</p>
 <p>Kontak: {{ $counts[$church->id]['contacts'] }}</p>
 <p>Status WhatsApp: {{ $counts[$church->id]['whatsapp'] }}</p>
 <span class="state">Fitur operasional menyusul</span>
</article>
@empty
<article class="feature glass"><span class="emoji">✨</span><h2>Belum ada gereja</h2><p>Buat gereja pertama untuk memulai.</p></article>
@endforelse
<article class="feature glass">
 <h2>Tambah Gereja</h2>
 <form method="POST" action="{{ route('churches.store') }}">
  @csrf
  <label for="church-name">Nama gereja</label>
  <input id="church-name" name="name" required minlength="3" maxlength="120" value="{{ old('name') }}" style="width:100%;padding:14px;border:1px solid #cad5e7;border-radius:12px;margin:12px 0">
  @error('name')<p role="alert">{{ $message }}</p>@enderror
  <button type="submit" class="button primary" style="border:0;cursor:pointer">Buat Gereja</button>
 </form>
</article>
</section>
<section class="wrap" style="padding-bottom:55px"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="button secondary" style="cursor:pointer">Keluar akun</button></form></section>
@endsection
