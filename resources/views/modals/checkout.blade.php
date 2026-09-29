@php $h = app(\App\Services\Hotel::class); $bal = $r->balance(); $early = $r->departure > $h->bizDate(); @endphp
@include('partials.mhead', ['title' => 'Check-out · '.$r->guest, 'sub' => 'Kamar '.$r->room_no.' · '.$r->type->name])
<form method="post" action="{{ route('fo.checkout', $r) }}">
  @csrf
  <input type="hidden" name="_back" value="{{ close_url() }}">
  <div class="modal-b">
    @if ($early)
      <div class="note warn">{{ ic('warn') }}<span>Tamu dijadwalkan pergi {{ fdate($r->departure) }}. Check-out hari ini akan dicatat sebagai <b>early departure</b>.</span></div>
    @endif
    @include('partials.folio-table')
    <div class="balance"><span>{{ $bal > 0 ? 'Harus dibayar tamu' : ($bal < 0 ? 'Kembalikan ke tamu' : 'Saldo lunas') }}</span><b>{{ rp(abs($bal)) }}</b></div>
    @if ($bal !== 0)
      <div class="form">
        <label class="fld">{{ $bal > 0 ? 'Jumlah dibayar (Rp)' : 'Jumlah dikembalikan (Rp)' }}<input name="pay" inputmode="numeric" value="{{ old('pay') }}" placeholder="{{ abs($bal) }}"></label>
        <label class="fld">Metode<select name="method">
          @foreach (\App\Http\Controllers\FrontOfficeController::PAY_METHODS as $m)<option @selected(old('method') === $m)>{{ $m }}</option>@endforeach
          @if ($bal > 0)<option @selected(old('method') === \App\Http\Controllers\FrontOfficeController::CITY_LEDGER)>{{ \App\Http\Controllers\FrontOfficeController::CITY_LEDGER }}</option>@endif
        </select></label>
      </div>
    @endif
    <label style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="key" value="1" style="width:18px;height:18px;accent-color:var(--pri)">Kartu kunci sudah dikembalikan tamu</label>
    @include('partials.inline-err')
  </div>
  <div class="modal-f"><a class="btn" href="{{ close_url() }}">Batal</a><button class="btn pri" type="submit">Selesaikan check-out</button></div>
</form>
