@extends('layouts.app')
@section('title', 'Restoran (POS)')
@section('content')
@php $h = app(\App\Services\Hotel::class); @endphp
<div class="head"><div><h1 class="serif">Widuri Resto · Kasir</h1><p>Catat pesanan, terima pembayaran, atau bebankan ke kamar tamu yang menginap.</p></div></div>
<div class="pos">
  <div style="display:flex;flex-direction:column;gap:14px">
    @include('partials.tabs', ['cur' => $cat, 'items' => array_map(fn ($c) => [$c, $c], array_merge(['Semua'], \App\Models\MenuItem::CATEGORIES)), 'url' => fn ($k) => route('pos.index', $k === 'Semua' ? [] : ['cat' => $k])])
    <div class="menu-grid">
      @foreach ($items as $m)
        <form method="post" action="{{ route('pos.add') }}" class="menu-form" data-soft>@csrf<input type="hidden" name="id" value="{{ $m->id }}">
          <button class="menu-item" type="submit"><b>{{ $m->name }}</b>@if($m->note)<small>{{ $m->note }}</small>@endif<span>{{ rp($m->price) }}</span></button>
        </form>
      @endforeach
    </div>
    <section class="card">
      <div class="card-h"><h2>Transaksi hari ini</h2><span style="color:var(--muted);font-size:13px">{{ $today->count() }} pesanan</span></div>
      @if ($today->isEmpty())
        <div class="empty">Belum ada transaksi.</div>
      @else
        <div class="tbl-wrap"><table class="tbl"><thead><tr><th>No.</th><th>Pesanan</th><th>Pembayaran</th><th class="num">Total</th></tr></thead><tbody>
        @foreach ($today as $o)
          <tr><td>{{ $o->code }}<span class="sub">{{ $o->created_by }}</span></td>
            <td>{{ collect($o->items)->map(fn ($i) => $i['qty'].'× '.$i['name'])->implode(', ') }}@if($o->table_no)<span class="sub">Meja {{ $o->table_no }}</span>@endif</td>
            <td>{{ $o->payment }}{{ $o->room_no ? ' · kamar '.$o->room_no : '' }}</td><td class="num">{{ rp($o->total) }}</td></tr>
        @endforeach
        </tbody></table></div>
      @endif
    </section>
  </div>

  <aside class="card" style="position:sticky;top:76px">
    <div class="card-h"><h2>Pesanan</h2>
      @if ($cart)<form method="post" action="{{ route('pos.clear') }}" data-soft>@csrf<button class="btn sm ghost" type="submit">Kosongkan</button></form>@endif
    </div>
    <div class="card-b">
      @forelse ($cart as $i => $c)
        <div class="cart-line">
          <div><b>{{ $c['name'] }}</b><div style="color:var(--muted);font-size:12px">{{ rp($c['price']) }}</div></div>
          <div class="qty">
            <form method="post" action="{{ route('pos.qty') }}" data-soft>@csrf<input type="hidden" name="i" value="{{ $i }}"><input type="hidden" name="d" value="-1"><button type="submit" aria-label="Kurangi {{ $c['name'] }}">−</button></form>
            <b>{{ $c['qty'] }}</b>
            <form method="post" action="{{ route('pos.qty') }}" data-soft>@csrf<input type="hidden" name="i" value="{{ $i }}"><input type="hidden" name="d" value="1"><button type="submit" aria-label="Tambah {{ $c['name'] }}">+</button></form>
          </div>
          <b style="font-variant-numeric:tabular-nums">{{ rp($c['price'] * $c['qty']) }}</b>
        </div>
      @empty
        <div class="empty" style="padding:16px">Pilih menu di sebelah kiri.</div>
      @endforelse
      <div style="margin-top:12px">
        <div class="sum"><span>Subtotal</span><span>{{ rp($sub) }}</span></div>
        <div class="sum"><span>Pajak &amp; layanan {{ $h->tax() }}%</span><span>{{ rp($tax) }}</span></div>
        <div class="sum total"><span>Total</span><span>{{ rp($sub + $tax) }}</span></div>
      </div>
      <form method="post" action="{{ route('pos.pay') }}">
        @csrf
        @php $pay = old('pay', 'Tunai'); @endphp
        <div class="form" style="margin-top:14px">
          <label class="fld">No. meja<input name="table" value="{{ old('table') }}" placeholder="mis. 5"></label>
          <label class="fld">Pembayaran<select name="pay" data-toggle-room>
            @foreach (\App\Http\Controllers\PosController::PAYMENTS as $v => $l)<option value="{{ $v }}" @selected($pay === $v)>{{ $l }}</option>@endforeach
          </select></label>
          <label class="fld full {{ $pay === 'Charge to room' ? '' : 'hidden' }}" id="pos-room">Kamar tamu<select name="room">
            <option value="">Pilih kamar…</option>
            @foreach ($rooms as $r)<option value="{{ $r->room_no }}" @selected(old('room') === $r->room_no)>{{ $r->room_no }} · {{ $r->guest }}</option>@endforeach
          </select><span class="hint">Hanya tamu yang sedang menginap</span></label>
        </div>
        <button class="btn grn lg" style="width:100%;margin-top:14px" type="submit" @disabled(! $cart)>Proses pembayaran</button>
      </form>
    </div>
  </aside>
</div>
@endsection
