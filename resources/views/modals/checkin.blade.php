@php $h = app(\App\Services\Hotel::class); $tot = $r->rate * $r->nights; @endphp
@include('partials.mhead', ['title' => 'Check-in · '.$r->guest, 'sub' => $r->code.' · '.$r->type->name.' · '.$r->nights.' malam'])
<form method="post" action="{{ route('fo.checkin', $r) }}">
  @csrf
  <input type="hidden" name="_back" value="{{ close_url() }}">
  <div class="modal-b">
    <div class="kv">
      <div><span>Menginap</span><b>{{ fshort($r->arrival) }} → {{ fshort($r->departure) }}</b></div>
      <div><span>Tamu</span><b>{{ $r->adults }} dewasa</b></div>
      <div><span>Sumber</span><b>{{ $r->source }}</b></div>
      <div><span>Estimasi tagihan</span><b>{{ rp($tot + $h->taxOf($tot)) }}</b></div>
    </div>
    @if ($r->note)
      <div class="note warn">{{ ic('warn') }}<span><b>Permintaan tamu:</b> {{ $r->note }}</span></div>
    @endif
    <div class="form">
      <label class="fld">No. identitas (KTP / paspor)<input name="idno" value="{{ old('idno', $r->idno) }}" placeholder="Wajib diisi"></label>
      <label class="fld">No. telepon<input name="phone" value="{{ old('phone', $r->phone) }}"></label>
    </div>
    <fieldset style="border:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px">
      <legend style="font-weight:700;margin-bottom:6px">Pilih kamar <span style="font-weight:400;color:var(--muted);font-size:13px">· tipe {{ $r->type->name }} ditampilkan lebih dulu</span></legend>
      <div class="pick-rooms" style="max-height:230px;overflow-y:auto;padding:6px">
        @foreach ($rooms as $x)
          <label><input type="radio" name="room" value="{{ $x->no }}" @checked(old('room') === $x->no)><span class="room st-{{ $x->status }}"><b>{{ $x->no }}</b><small>{{ $x->status }}</small><i>{{ $x->room_type_code === $r->room_type_code ? explode(' ', $x->type->name)[0] : 'Tipe lain' }}</i></span></label>
        @endforeach
      </div>
    </fieldset>
    <div class="form">
      <label class="fld">Deposit<input name="deposit" inputmode="numeric" value="{{ old('deposit', 500000) }}"></label>
      <label class="fld">Metode deposit<select name="method">
        @foreach (\App\Http\Controllers\FrontOfficeController::PAY_METHODS as $m)<option @selected(old('method') === $m)>{{ $m }}</option>@endforeach
      </select></label>
      <label class="fld">Jumlah kartu kunci<input name="keys" type="number" min="1" max="4" value="{{ old('keys', 2) }}"></label>
    </div>
    @include('partials.inline-err')
  </div>
  <div class="modal-f"><a class="btn" href="{{ close_url() }}">Batal</a><button class="btn pri" type="submit">Selesaikan check-in</button></div>
</form>
