<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#edf3ff">
<title>@yield('title', 'Church Center — Gereja Terhubung')</title>
<meta name="description" content="Church Center, platform pengelolaan gereja dan otomatisasi komunikasi WhatsApp.">
<link rel="stylesheet" href="{{ asset('church-center.css') }}">
</head>
<body>
<div class="ambient"></div>
<header class="nav glass">
  <a class="brand" href="{{ route('home') }}"><span class="brand-mark">✦</span> Church<span>Center</span></a>
  <nav aria-label="Navigasi utama">
    <a href="{{ route('home') }}">Beranda</a>
    <a href="{{ route('features') }}">Fitur</a>
    <a href="{{ route('pricing') }}">Lisensi</a>
  </nav>
  @auth
  <a class="nav-cta" href="{{ route('dashboard') }}">Dashboard</a>
  @else
  <a class="nav-cta" href="{{ route('login') }}">Masuk</a>
  @endauth
</header>
<main>@yield('content')</main>
<footer class="footer"><div><strong>✦ Church Center</strong><p>Komunikasi gereja lebih tertata, pelayanan lebih terhubung.</p></div><span>© {{ date('Y') }} Church Center · Starter Preview</span></footer>
</body>
</html>
