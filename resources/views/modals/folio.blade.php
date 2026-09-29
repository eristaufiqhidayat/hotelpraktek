@include('partials.mhead', ['title' => 'Folio · '.$r->guest, 'sub' => 'Kamar '.$r->room_no.' · '.$r->type->name.' · '.fshort($r->arrival).' → '.fshort($r->departure)])
<div class="modal-b">
  @include('partials.folio-table')
  <div class="balance"><span>Saldo tagihan</span><b>{{ rp($r->balance()) }}</b></div>
  <form method="post" action="{{ route('fo.post', $r) }}" class="card" style="padding:14px">
    @csrf
    <b style="display:block;margin-bottom:10px">Posting tagihan atau pembayaran</b>
    <div class="form">
      <label class="fld">Jenis<select name="dept">
        @foreach (['Laundry' => 'Laundry', 'Minibar' => 'Minibar', 'Telepon' => 'Telepon', 'Lain-lain' => 'Lain-lain', 'Pembayaran' => 'Pembayaran dari tamu'] as $v => $l)<option value="{{ $v }}" @selected(old('dept') === $v)>{{ $l }}</option>@endforeach
      </select></label>
      <label class="fld">Jumlah (Rp)<input name="amount" inputmode="numeric" value="{{ old('amount') }}" placeholder="mis. 75000"></label>
      <label class="fld full">Keterangan<input name="desc" value="{{ old('desc') }}" placeholder="mis. Laundry 5 potong"></label>
    </div>
    @include('partials.inline-err')
    <div style="display:flex;justify-content:flex-end;margin-top:10px"><button class="btn pri" type="submit">Posting</button></div>
  </form>
</div>
<div class="modal-f">
  <a class="btn" href="{{ route('fo.print', $r) }}" target="_blank">{{ ic('print', 16) }}Cetak folio</a>
  <a class="btn pri" href="{{ modal_url('checkout', $r->id) }}">Lanjut check-out</a>
</div>
