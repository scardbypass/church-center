@extends('layouts.church-center')
@section('title', 'Masuk — Church Center')
@section('content')
<section class="wrap page-heading">
 <div class="eyebrow">Akun Fulltime</div>
 <h1>Masuk ke<br><em>Church Center.</em></h1>
 <p class="lead">Gunakan akun Google untuk mengakses dashboard gereja.</p>
 @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
 <div class="actions"><a class="button primary" href="{{ route('auth.google') }}">Masuk dengan Google ↗</a></div>
 <p class="notice">Login Google memerlukan GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URI di server.</p>
</section>
@endsection
