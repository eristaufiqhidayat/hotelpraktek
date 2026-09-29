@extends('layouts.app')
@section('title', 'Housekeeping')
@section('content')
@php $ST = \App\Models\Room::STATUS; $STS = \App\Models\Room::STATUS_SHORT; @endphp
<div class="head"><div><h1 class="serif">Housekeeping</h1><p>Perbarui status kamar setelah dibersihkan, tugaskan room attendant, dan laporkan kerusakan.</p></div></div>
<div class="legend">
  <a class="{{ $filter === 'all' ? 'on' : '' }}" href="{{ route('hk.index', array_filter(['room' => $sel?->no])) }}">Semua <b>{{ $rooms->count() }}</b></a>
  @foreach ($STS as $k => $label)
    <a class="{{ $filter === $k ? 'on' : '' }}" href="{{ route('hk.index', array_filter(['st' => $k, 'room' => $sel?->no])) }}"><span class="sw st-{{ $k }}"></span>{{ $k }} · {{ $label }} <b>{{ $counts[$k] ?? 0 }}</b></a>
  @endforeach
</div>
<div class="grid-side">
  <section class="card"><div class="card-b" style="display:flex;flex-direction:column;gap:18px">
    @foreach ($floors as $f => $rs)
      <div class="floor">
        <h3>Lantai {{ $f }} · {{ $rs->pluck('type.name')->unique()->implode(' & ') }}</h3>
        <div class="rooms">
          @foreach ($rs as $r)
            @php $g = $r->currentStay; @endphp
            <a class="room st-{{ $r->status }} {{ $filter !== 'all' && $r->status !== $filter ? 'dim' : '' }} {{ $sel?->no === $r->no ? 'sel' : '' }}"
               href="{{ route('hk.index', array_filter(['st' => $filter !== 'all' ? $filter : null, 'room' => $r->no])) }}"
               aria-label="Kamar {{ $r->no }}, {{ $ST[$r->status] }}"><b>{{ $r->no }}</b><small>{{ $r->status }}</small><i>{{ $g ? explode(' ', $g->guest)[0] : ($r->attendant ? 'RA: '.$r->attendant : ' ') }}</i></a>
          @endforeach
        </div>
      </div>
    @endforeach
  </div></section>

  <aside class="card">
  @if (! $sel)
    <div class="card-b"><div class="note info">{{ ic('info') }}<span>Pilih kamar di denah untuk melihat detail dan mengubah statusnya.</span></div>
      @include('housekeeping.issues')</div>
  @else
    @php
      $g = $sel->currentStay;
      $chip = $sel->status === 'VC' ? 'c-ok' : ($sel->status === 'OOO' ? 'c-out' : (str_contains($sel->status, 'D') ? 'c-ns' : 'c-conf'));
    @endphp
    <div class="card-h"><div><h2 style="font-size:22px">Kamar {{ $sel->no }}</h2><span style="color:var(--muted);font-size:13px">{{ $sel->type->name }} · Lantai {{ $sel->floor }}</span></div><span class="chip {{ $chip }}">{{ $sel->status }}</span></div>
    <div class="card-b" style="display:flex;flex-direction:column;gap:14px">
      <div><b>{{ $ST[$sel->status] }}</b>
        @if ($g)<div style="color:var(--ink-2);margin-top:4px">Tamu: {{ $g->guest }} (s.d. {{ fshort($g->departure) }})</div>@endif
        @if ($sel->ooo_note)<div class="note warn" style="margin-top:8px">{{ ic('warn') }}<span>{{ $sel->ooo_note }}</span></div>@endif
      </div>
      <form method="post" action="{{ route('hk.attendant', $sel) }}" data-soft>
        @csrf
        <label class="fld">Room attendant<select name="attendant" data-autosubmit>
          <option value="">Belum ditugaskan</option>
          @foreach ($students as $s)<option value="{{ $s->first_name }}" @selected($sel->attendant === $s->first_name)>{{ $s->name }}</option>@endforeach
        </select></label>
      </form>
      <div style="display:flex;flex-direction:column;gap:8px">
        @foreach (\App\Http\Controllers\HousekeepingController::FLOW[$sel->status] ?? [] as [$to, $label, $cls])
          <form method="post" action="{{ route('hk.status', $sel) }}" data-soft style="display:flex">@csrf<input type="hidden" name="status" value="{{ $to }}"><button class="btn {{ $cls }}" style="flex:1" type="submit">{{ $label }}</button></form>
        @endforeach
        @if ($sel->status !== 'OOO')
          <a class="btn danger" href="{{ modal_url('ooo', $sel->no) }}">{{ ic('warn', 16) }}Laporkan kerusakan / OOO</a>
        @endif
      </div>
      @include('housekeeping.issues')
    </div>
  @endif
  </aside>
</div>
@endsection
