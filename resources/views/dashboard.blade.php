@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
@php $h = app(\App\Services\Hotel::class); @endphp
<div class="head">
  <div><h1 class="serif">Beranda</h1><p>{{ $h->isGuru() ? 'Pantau kondisi hotel praktik hari ini.' : 'Selamat bertugas di bagian '.$h->user()['dept'].'.' }}</p></div>
  <div class="acts">
    <a class="btn" href="{{ modal_url('walkin') }}">{{ ic('plus', 16) }}Tamu walk-in</a>
    <a class="btn pri" href="{{ modal_url('newres') }}">{{ ic('plus', 16) }}Reservasi baru</a>
  </div>
</div>
<div class="kpis">
  @include('partials.kpi', ['l' => 'Okupansi malam ini', 'v' => round($inhouse / max(1, $sellable) * 100).'%', 's' => $inhouse.' dari '.$sellable.' kamar terisi'])
  @include('partials.kpi', ['l' => 'Kedatangan hari ini', 'v' => $arrivals->count(), 's' => 'belum check-in'])
  @include('partials.kpi', ['l' => 'Keberangkatan hari ini', 'v' => $departures->count(), 's' => 'belum check-out'])
  @include('partials.kpi', ['l' => 'Kamar siap jual', 'v' => $vc, 's' => $dirty.' kamar perlu dibersihkan'])
  @include('partials.kpi', ['l' => 'Pendapatan resto hari ini', 'v' => rp($fnbToday), 's' => 'belum termasuk pajak'])
</div>
<div class="grid2">
  <section class="card"><div class="card-h"><h2>Kedatangan hari ini</h2><a class="btn sm ghost" href="{{ route('fo.index') }}">Lihat semua</a></div>
    @include('partials.mini-list', ['list' => $arrivals, 'kind' => 'arr'])</section>
  <section class="card"><div class="card-h"><h2>Keberangkatan hari ini</h2><a class="btn sm ghost" href="{{ route('fo.index', ['tab' => 'departure']) }}">Lihat semua</a></div>
    @include('partials.mini-list', ['list' => $departures, 'kind' => 'dep'])</section>
</div>
<section class="card"><div class="card-h"><h2>Status kamar</h2><a class="btn sm ghost" href="{{ route('hk.index') }}">Buka housekeeping</a></div>
  <div class="card-b"><div class="legend">
    @foreach (\App\Models\Room::STATUS_SHORT as $k => $label)
      <a href="{{ route('hk.index', ['st' => $k]) }}"><span class="sw st-{{ $k }}"></span>{{ $k }} · {{ $label }} <b>{{ $counts[$k] ?? 0 }}</b></a>
    @endforeach
  </div></div>
</section>
@endsection
