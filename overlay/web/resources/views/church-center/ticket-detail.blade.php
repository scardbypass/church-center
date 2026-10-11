@extends('layouts.church-center')
@section('title', 'Detail Tiket')
@section('content')
<section class="wrap page-heading"><h1>{{ $ticket->subject }}</h1><p>{{ $ticket->church_name }} · {{ $ticket->status }}</p></section>
<section class="wrap">
<article class="glass" style="padding:20px"><p style="white-space:pre-wrap">{{ $ticket->message }}</p></article>
@foreach($replies as $reply)
<article class="glass" style="padding:20px;margin-top:12px"><strong>{{ $reply->author_name }}</strong><p style="white-space:pre-wrap">{{ $reply->message }}</p></article>
@endforeach
@if($ticket->status === 'open')
<form method="POST" action="{{ route('tickets.reply', $ticket->id) }}" style="margin:24px 0">@csrf
<label>Balasan <textarea name="message" minlength="2" maxlength="10000" required style="width:100%"></textarea></label>
<button class="button primary">Kirim Balasan</button></form>
@endif
</section>
@endsection
