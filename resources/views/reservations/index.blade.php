@extends('layouts.app')
@section('title', 'Reservasi')
@section('content')
@php $h = app(\App\Services\Hotel::class); $b = $h->bizDate(); $types = $h->types(); @endphp
<div class="head">
  <div><h1 class="serif">Reservasi</h1><p>Kelola pemesanan kamar, cek ketersediaan, dan jadwalkan kedatangan tamu.</p></div>
  <div class="acts"><a class="btn pri" href="{{ modal_url('newres') }}">{{ ic('plus', 16) }}Reservasi baru</a></div>
</div>
<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
  @include('partials.tabs', ['cur' => $tab, 'items' => collect(\App\Http\Controllers\ReservationController::TABS)->map(fn ($l, $k) => [$k, $l])->values()->all(), 'url' => fn ($k) => route('res.index', array_filter(['tab' => $k, 'q' => $q]))])
  <form method="get" action="{{ route('res.index') }}" role="search">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="sr" for="resq">Cari reservasi</label>
    <input id="resq" class="search" type="search" name="q" placeholder="Cari nama tamu, nomor, atau kamar…" value="{{ $q }}" data-live-filter="#resbody">
  </form>
</div>
<section class="card"><div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>No. / Tamu</th><th>Tipe kamar</th><th>Menginap</th><th>Sumber</th><th>Status</th><th class="num">Tarif/malam</th><th class="num">Aksi</th></tr></thead>
  <tbody id="resbody">
  @forelse ($list as $r)
    <tr data-search="{{ strtolower($r->guest.' '.$r->code.' '.$r->room_no) }}">
      <td><b>{{ $r->guest }}</b><span class="sub">{{ $r->code }}{{ $r->nationality !== 'Indonesia' ? ' · '.$r->nationality : '' }}{{ $r->note ? ' · '.$r->note : '' }}</span></td>
      <td>{{ $r->type->name }}@if($r->room_no)<span class="sub">Kamar {{ $r->room_no }}</span>@endif</td>
      <td>{{ fshort($r->arrival) }} → {{ fshort($r->departure) }}<span class="sub">{{ $r->nights }} malam · {{ $r->adults }} dewasa</span></td>
      <td>{{ $r->source }}</td>
      <td><span class="chip {{ \App\Models\Reservation::CHIP[$r->status] }}">{{ \App\Models\Reservation::LABEL[$r->status] }}</span></td>
      <td class="num">{{ rp($r->rate) }}</td>
      <td class="num"><div class="acts-cell">
        @if ($r->status === 'Confirmed' && $r->arrival === $b)<a class="btn sm pri" href="{{ modal_url('checkin', $r->id) }}">Check-in</a>@endif
        @if ($r->status === 'In-house')<a class="btn sm" href="{{ modal_url('folio', $r->id) }}">Folio</a>@endif
        @if ($r->status === 'Confirmed')
          <form method="post" action="{{ route('res.cancel', $r) }}" data-confirm="Batalkan reservasi {{ $r->guest }}?">@csrf<button class="btn sm danger" type="submit">Batalkan</button></form>
        @endif
        @if (! in_array($r->status, ['Confirmed', 'In-house']))<span class="sub">–</span>@endif
      </div></td>
    </tr>
  @empty
    <tr><td colspan="7" class="empty">Tidak ada reservasi.</td></tr>
  @endforelse
  </tbody>
</table></div></section>
<section class="card">
  <div class="card-h"><h2>Ketersediaan kamar 7 hari ke depan</h2><span class="sub" style="color:var(--muted);font-size:12px">Sisa kamar yang bisa dijual per malam</span></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Tipe</th>@foreach ($days as $d)<th class="num">{{ fshort($d) }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach ($types as $code => $t)
      <tr><td><b>{{ $t->name }}</b><span class="sub">{{ rp($t->rate) }}</span></td>
        @foreach ($days as $d)
          @php $a = $avail[$code][$d]; @endphp
          <td class="num" style="font-weight:700;color:{{ $a <= 0 ? 'var(--red)' : ($a <= 1 ? 'var(--amb)' : 'var(--grn)') }}">{{ $a }}</td>
        @endforeach
      </tr>
    @endforeach
    </tbody>
  </table></div>
</section>
@endsection
