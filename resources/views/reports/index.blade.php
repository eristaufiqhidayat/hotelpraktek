@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
@php $h = app(\App\Services\Hotel::class); @endphp
<div class="head">
  <div><h1 class="serif">Laporan</h1><p>Kinerja hotel praktik berdasarkan hasil night audit.</p></div>
  <div class="acts">
    @include('partials.tabs', ['cur' => (string) $days, 'items' => [['7', '7 hari'], ['14', '14 hari'], ['30', '30 hari']], 'url' => fn ($k) => route('report', ['days' => $k])])
    <button class="btn" type="button" data-print>{{ ic('print', 16) }}Cetak</button>
  </div>
</div>
<div class="kpis">
  @include('partials.kpi', ['l' => 'Okupansi rata-rata', 'v' => pct($sum['avail'] ? $sum['occ'] / $sum['avail'] * 100 : 0), 's' => $sum['occ'].' kamar-malam terjual'])
  @include('partials.kpi', ['l' => 'ADR', 'v' => rp($sum['occ'] ? $sum['room'] / $sum['occ'] : 0), 's' => 'rata-rata tarif kamar'])
  @include('partials.kpi', ['l' => 'RevPAR', 'v' => rp($sum['avail'] ? $sum['room'] / $sum['avail'] : 0), 's' => 'pendapatan per kamar tersedia'])
  @include('partials.kpi', ['l' => 'Pendapatan kamar', 'v' => rp($sum['room'])])
  @include('partials.kpi', ['l' => 'Pendapatan F&B', 'v' => rp($sum['fnb'])])
</div>
<section class="card">
  <div class="card-h"><h2>Pendapatan harian</h2><span style="color:var(--muted);font-size:12px">Kamar + F&amp;B + lainnya, sebelum pajak. Hari ini dihitung setelah night audit.</span></div>
  <div class="card-b"><div class="bars" role="img" aria-label="Grafik pendapatan harian">
    @foreach ($rows as $r)
      <div class="bar"><b>{{ $r['total'] ? number_format(round($r['total'] / 1000), 0, ',', '.').'rb' : '–' }}</b><i class="{{ $r['date'] === $h->bizDate() ? 'today' : '' }}" style="height:{{ max(1, $r['total'] / $max * 130) }}px"></i><small>{{ fshort($r['date']) }}</small></div>
    @endforeach
  </div></div>
</section>
<section class="card"><div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>Tanggal</th><th class="num">Terjual</th><th class="num">Okupansi</th><th class="num">ADR</th><th class="num">RevPAR</th><th class="num">Kamar</th><th class="num">F&amp;B</th><th class="num">Lainnya</th><th class="num">Total</th></tr></thead>
  <tbody>
  @foreach (array_reverse($rows) as $r)
    <tr><td>{{ fshort($r['date']) }}@if($r['date'] === $h->bizDate()) <span class="chip c-conf">berjalan</span>@endif</td>
      <td class="num">{{ $r['occ'] }}/{{ $r['avail'] }}</td><td class="num">{{ pct($r['pct']) }}</td><td class="num">{{ rp($r['adr']) }}</td><td class="num">{{ rp($r['revpar']) }}</td>
      <td class="num">{{ rp($r['roomRev']) }}</td><td class="num">{{ rp($r['fnb']) }}</td><td class="num">{{ rp($r['other']) }}</td><td class="num"><b>{{ rp($r['total']) }}</b></td></tr>
  @endforeach
  </tbody>
</table></div></section>
@endsection
