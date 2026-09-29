@extends('layouts.app')
@section('title', 'Night Audit')
@section('content')
@php $h = app(\App\Services\Hotel::class); $blocked = $departures->isNotEmpty(); @endphp
<div class="head"><div><h1 class="serif">Night Audit</h1><p>Tutup hari operasional {{ fdate($h->bizDate(), true) }} dan lanjut ke hari berikutnya.</p></div></div>
<div class="grid-side">
  <section class="card"><div class="card-b">
    <div class="steps">
      @component('audit.step', ['n' => 1, 'cls' => $arrivals->isNotEmpty() ? 'warn' : 'done', 'title' => 'Kedatangan yang belum check-in'])
        @if ($arrivals->isNotEmpty())
          {{ $arrivals->count() }} reservasi belum datang. Saat audit dijalankan, reservasi ini akan ditandai <b>no-show</b>.
          <ul style="margin:6px 0 0;padding-left:18px">@foreach ($arrivals as $r)<li>{{ $r->guest }} · {{ $r->type->name }}</li>@endforeach</ul>
        @else
          Semua tamu yang dijadwalkan sudah check-in.
        @endif
      @endcomponent
      @component('audit.step', ['n' => 2, 'cls' => $blocked ? 'warn' : 'done', 'title' => 'Tamu yang seharusnya check-out'])
        @if ($blocked)
          <span class="inline-err">Harus diselesaikan dulu.</span> Lakukan check-out atau perpanjang masa inap:
          @foreach ($departures as $r)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:6px 0;flex-wrap:wrap">{{ $r->guest }} · kamar {{ $r->room_no }}
              <span style="display:flex;gap:6px">
                <form method="post" action="{{ route('fo.extend', $r) }}">@csrf<button class="btn sm" type="submit">Perpanjang 1 malam</button></form>
                <a class="btn sm pri" href="{{ modal_url('checkout', $r->id) }}">Check-out</a>
              </span>
            </div>
          @endforeach
        @else
          Tidak ada tamu yang melewati tanggal keberangkatan.
        @endif
      @endcomponent
      @component('audit.step', ['n' => 3, 'cls' => '', 'title' => 'Posting tarif kamar'])
        {{ $inhouse->count() }} kamar terisi akan dibebankan tarif malam ini: <b>{{ rp($roomTotal) }}</b> + pajak &amp; layanan {{ $h->tax() }}% ({{ rp($h->taxOf($roomTotal)) }}).
      @endcomponent
      @component('audit.step', ['n' => 4, 'cls' => '', 'title' => 'Perbarui status kamar & tanggal'])
        Kamar terisi berubah menjadi <b>OD</b> untuk pembersihan harian, lalu tanggal hotel maju ke <b>{{ fdate(\App\Support\Fmt::addDays($h->bizDate(), 1), true) }}</b>.
      @endcomponent
    </div>
    <form method="post" action="{{ route('audit.run') }}" data-confirm="Jalankan night audit dan tutup hari {{ fdate($h->bizDate()) }}?" style="display:flex;justify-content:flex-end;margin-top:18px">
      @csrf<button class="btn pri lg" type="submit" @disabled($blocked)>{{ ic('moon') }}Jalankan night audit</button>
    </form>
  </div></section>
  <aside class="card"><div class="card-h"><h2>Hasil hari sebelumnya</h2></div>
    <div class="card-b" style="display:flex;flex-direction:column;gap:10px">
      <div class="sum"><span>Tanggal</span><b>{{ fdate($prev['date']) }}</b></div>
      <div class="sum"><span>Okupansi</span><b>{{ pct($prev['pct']) }}</b></div>
      <div class="sum"><span>ADR (rata-rata tarif)</span><b>{{ rp($prev['adr']) }}</b></div>
      <div class="sum"><span>RevPAR</span><b>{{ rp($prev['revpar']) }}</b></div>
      <div class="sum"><span>Pendapatan kamar</span><b>{{ rp($prev['roomRev']) }}</b></div>
      <div class="sum"><span>Pendapatan F&amp;B</span><b>{{ rp($prev['fnb']) }}</b></div>
      <div class="note info" style="margin-top:6px">{{ ic('info') }}<span><b>ADR</b> = pendapatan kamar ÷ kamar terjual. <b>RevPAR</b> = pendapatan kamar ÷ kamar tersedia.</span></div>
    </div>
  </aside>
</div>
@endsection
