@extends('layouts.church-center')
@section('title', 'Church Center — Platform Pelayanan Gereja')
@section('content')
<section class="hero wrap">
  <div class="eyebrow"><span class="pulse"></span> Platform pelayanan gereja generasi baru</div>
  <h1>Lebih dekat dengan jemaat.<br><em>Lebih mudah melayani.</em></h1>
  <p class="lead">Satu tempat untuk komunikasi gereja, jadwal ibadah, renungan harian, dan otomatisasi WhatsApp — dirancang untuk tim Fulltime.</p>
  <div class="actions"><a class="button primary" href="{{ route('features') }}">Jelajahi Fitur <span>↗</span></a><a class="button secondary" href="{{ route('pricing') }}">Lihat Lisensi</a></div>
  <div class="notice">Ini adalah tampilan awal yang sudah berjalan. Login, bot, pembayaran, dan otomatisasi masih dalam pengembangan.</div>
</section>
<section class="wrap grid" aria-label="Fitur unggulan">
  <article class="feature glass"><span class="emoji">💬</span><h2>WhatsApp Bot</h2><p>Menu gereja, balasan otomatis, dan FAQ yang dapat diatur per gereja.</p><span class="state">Direncanakan</span></article>
  <article class="feature glass"><span class="emoji">☀️</span><h2>Ibadah Harian</h2><p>Jadwalkan renungan pagi, siang, dan malam ke grup yang ditentukan.</p><span class="state">Direncanakan</span></article>
  <article class="feature glass"><span class="emoji">🗓️</span><h2>Jadwal & Acara</h2><p>Pengingat ibadah mingguan, acara khusus, dan ulang tahun jemaat.</p><span class="state">Direncanakan</span></article>
  <article class="feature glass"><span class="emoji">🛡️</span><h2>Multi-Gereja</h2><p>Pengaturan, anggota tim, dan data setiap gereja terpisah.</p><span class="state">Direncanakan</span></article>
</section>
<section class="wrap bottom-cta glass"><h2>Teknologi yang membantu pelayanan, bukan mempersulitnya.</h2><p>Church Center dibangun bertahap dengan arsitektur modular dan dokumentasi terbuka.</p><a class="button primary" href="{{ route('features') }}">Lihat seluruh rencana fitur ↗</a></section>
@endsection
