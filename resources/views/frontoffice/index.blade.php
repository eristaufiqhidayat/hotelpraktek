@extends('layouts.app')
@section('title', 'Front Office')
@section('content')
@php
  $h = app(\App\Services\Hotel::class);
  $list = ['arrival' => $arrivals, 'inhouse' => $inhouse, 'departure' => $departures][$tab];
  $kind = ['arrival' => 'arr', 'inhouse' => 'ih', 'departure' => 'dep'][$tab];
@endphp
<div class="head">
  <div><h1 class="serif">Front Office</h1><p>Check-in, tamu yang menginap, tagihan (folio), dan check-out.</p></div>
  <div class="acts"><a class="btn" href="{{ modal_url('walkin') }}">{{ ic('plus', 16) }}Tamu walk-in</a></div>
</div>
@include('partials.tabs', ['cur' => $tab, 'items' => [['arrival', 'Kedatangan', $arrivals->count()], ['inhouse', 'Tamu menginap', $inhouse->count()], ['departure', 'Keberangkatan', $departures->count()]], 'url' => fn ($k) => route('fo.index', ['tab' => $k])])
<section class="card">
@if ($list->isEmpty())
  <div class="empty">{{ ['arr' => 'Tidak ada kedatangan yang tersisa hari ini.', 'ih' => 'Belum ada tamu yang menginap.', 'dep' => 'Tidak ada tamu yang harus check-out hari ini.'][$kind] }}</div>
@else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Tamu</th><th>Kamar</th><th>Menginap</th><th>Sumber</th><th class="num">Saldo folio</th><th class="num">Aksi</th></tr></thead>
    <tbody>
    @foreach ($list as $r)
      <tr>
        <td><b>{{ $r->guest }}</b><span class="sub">{{ $r->code }}{{ $r->note ? ' · '.$r->note : '' }}</span></td>
        <td>@if($r->room_no)<b>{{ $r->room_no }}</b>@else<span class="sub">Belum ditentukan</span>@endif<span class="sub">{{ $r->type->name }}</span></td>
        <td>{{ fshort($r->arrival) }} → {{ fshort($r->departure) }}@if($r->status === 'In-house' && $r->departure < $h->bizDate()) <span class="chip c-err">Lewat tanggal</span>@endif</td>
        <td>{{ $r->source }}</td>
        <td class="num">{{ $kind === 'arr' ? '–' : rp($r->balance()) }}</td>
        <td class="num"><div class="acts-cell">
          @if ($kind === 'arr')
            <a class="btn sm pri" href="{{ modal_url('checkin', $r->id) }}">Check-in</a>
          @else
            <a class="btn sm" href="{{ modal_url('folio', $r->id) }}">Folio</a>
            <a class="btn sm {{ $kind === 'dep' ? 'pri' : '' }}" href="{{ modal_url('checkout', $r->id) }}">Check-out</a>
          @endif
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
@endif
</section>
<div class="note info">{{ ic('info') }}<span><b>Alur standar:</b> cocokkan identitas tamu dengan reservasi, pilih kamar berstatus <b>VC (siap)</b>, terima deposit, lalu serahkan kartu kunci. Saat check-out, pastikan saldo folio lunas.</span></div>
@endsection
