@extends('layouts.church-center')
@section('title', 'Kontak Jemaat — Church Center')
@section('content')
<section class="wrap page-heading" style="text-align:left;padding-bottom:35px">
<a href="{{ route('dashboard') }}">← Dashboard</a>
<div class="eyebrow" style="display:flex;margin-top:25px">Kontak Jemaat</div>
<h1 style="font-size:clamp(32px,5vw,52px)">{{ $church->name }}</h1>
<p class="lead" style="margin:0">Kelola kontak gereja. Kontak tanpa persetujuan tidak boleh menerima broadcast.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
</section>
<section class="wrap glass" style="padding:26px;border-radius:24px;margin-bottom:25px">
<h2>Tambah Kontak</h2>
<form method="POST" action="{{ route('churches.contacts.store', $church->id) }}" style="display:grid;gap:12px;max-width:600px">
@csrf
<label>Nama <input required name="name" minlength="2" maxlength="255" value="{{ old('name') }}" style="width:100%;padding:12px;border-radius:12px;border:1px solid #ccd5e5"></label>
<label>WhatsApp <input required name="phone" inputmode="tel" placeholder="081234567890" value="{{ old('phone') }}" style="width:100%;padding:12px;border-radius:12px;border:1px solid #ccd5e5"></label>
<label>Tanggal lahir (opsional) <input type="date" name="birthday" value="{{ old('birthday') }}" style="width:100%;padding:12px;border-radius:12px;border:1px solid #ccd5e5"></label>
<label><input type="checkbox" name="consent" value="1"> Jemaat telah memberi persetujuan untuk menerima pesan</label>
@if($errors->any())<p role="alert" style="color:#ba1e37">{{ $errors->first() }}</p>@endif
<button class="button primary" style="border:0;cursor:pointer">Simpan Kontak</button>
</form>
</section>
<section class="wrap glass" style="padding:26px;border-radius:24px;margin-bottom:25px">
<h2>Impor CSV</h2><p>Kolom wajib: <code>name,phone</code>. Maksimal 2 MB atau 5000 baris. Kontak impor tidak otomatis dianggap menyetujui pesan.</p>
<form method="POST" action="{{ route('churches.contacts.import', $church->id) }}" enctype="multipart/form-data">@csrf
<input type="file" name="file" accept=".csv,text/csv" required><button class="button secondary">Impor Kontak</button></form>
</section>
<section class="wrap glass" style="padding:26px;border-radius:24px;margin-bottom:70px;overflow-x:auto">
<h2>Daftar Kontak</h2>
<table style="width:100%;border-collapse:collapse;text-align:left;min-width:540px">
<thead><tr><th>Nama</th><th>WhatsApp</th><th>Persetujuan</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($contacts as $contact)
<tr style="border-top:1px solid #dde5f2">
<td style="padding:15px 6px">{{ $contact->name }}
<details><summary>Edit</summary>
<form method="POST" action="{{ route('churches.contacts.update', [$church->id, $contact->id]) }}">@csrf @method('PATCH')
<input name="name" required minlength="2" maxlength="255" value="{{ $contact->name }}" aria-label="Nama">
<input type="date" name="birthday" value="{{ $contact->birthday }}" aria-label="Tanggal lahir">
<button>Simpan</button></form></details></td>
<td>{{ $contact->phone_e164 }}</td>
<td>{{ $contact->consent_at ? 'Ya' : 'Belum' }}</td>
<td>@if($contact->consent_at)<form method="POST" action="{{ route('churches.contacts.revoke', [$church->id, $contact->id]) }}">@csrf @method('PATCH')<button>Cabut persetujuan</button></form>@endif
<form method="POST" action="{{ route('churches.contacts.destroy', [$church->id, $contact->id]) }}">@csrf @method('DELETE')<button style="cursor:pointer" onclick="return confirm('Hapus kontak ini?')">Hapus</button></form></td>
</tr>
@empty
<tr><td colspan="4" style="padding:20px">Belum ada kontak.</td></tr>
@endforelse
</tbody>
</table>
{{ $contacts->links() }}
</section>
@endsection
