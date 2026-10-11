@extends('layouts.church-center')
@section('title', 'Fitur — Church Center')
@section('content')
<section class="wrap page-heading"><div class="eyebrow">Rencana fitur</div><h1>Semua kebutuhan pelayanan.<br><em>Satu platform.</em></h1><p class="lead">Daftar modul di bawah adalah roadmap produk, bukan klaim fitur yang sudah aktif.</p></section>
<section class="wrap grid">
@foreach([
['🔐','Akun & Gereja','Google Login, tim Fulltime, hak akses, dan isolasi data per gereja.'],
['📇','Kontak Jemaat','Kontak manual, CSV/Excel, Google Contacts, dan persetujuan penerima.'],
['📱','WhatsApp Bot','QR/pairing, koneksi per gereja, menu, Auto Reply, dan FAQ.'],
['🌅','Auto IH','Renungan pagi, siang, malam, masing-masing dengan sakelar sendiri.'],
['📣','Broadcast','Pesan langsung, terjadwal, dan berulang dengan antrean pengiriman.'],
['🎂','Ulang Tahun','Template ucapan otomatis yang dapat diedit dan dipratinjau.'],
['🗓️','Jadwal & Acara','Pengingat ibadah, Natal, Paskah, baptisan, dan acara khusus.'],
['💳','Lisensi & Pembayaran','Paket 1/3/6/12 bulan, transfer manual, QRIS jika integrasi tersedia.'],
['🎫','Tiket Bantuan','Tiket Fulltime ke Admin Pusat, lampiran dan status.'],
['🧰','Backup & Restore','Cadangan konfigurasi per gereja tanpa menyimpan rahasia.'],
['🛡️','Admin Pusat','Kontrol layanan global, maintenance, audit, dan monitoring.'],
['📊','Laporan','Statistik pengiriman, status bot, dan lisensi.']
] as [$icon,$title,$description])
<article class="feature glass"><span class="emoji">{{ $icon }}</span><h2>{{ $title }}</h2><p>{{ $description }}</p><span class="state">Roadmap</span></article>
@endforeach
</section>
@endsection
