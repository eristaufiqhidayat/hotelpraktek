@php $walk = $modal === 'walkin'; $biz = app(\App\Services\Hotel::class)->bizDate(); @endphp
@include('partials.mhead', ['title' => $walk ? 'Tamu walk-in' : 'Reservasi baru', 'sub' => $walk ? 'Tamu datang langsung tanpa pemesanan. Setelah disimpan, lanjut ke check-in.' : 'Cek ketersediaan sebelum menyimpan.'])
<form method="post" action="{{ route('res.store') }}">
  @csrf
  <input type="hidden" name="walkin" value="{{ $walk ? 1 : 0 }}">
  <input type="hidden" name="_back" value="{{ close_url() }}">
  <div class="modal-b">
    <div class="form">
      <label class="fld">Nama tamu<input name="guest" value="{{ old('guest') }}" required autocomplete="off"></label>
      <label class="fld">No. telepon<input name="phone" value="{{ old('phone') }}" inputmode="tel"></label>
      <label class="fld">Kebangsaan<input name="nat" value="{{ old('nat', 'Indonesia') }}"></label>
      <label class="fld">Jumlah dewasa<input name="adults" type="number" min="1" max="4" value="{{ old('adults', 2) }}"></label>
      <label class="fld">Tanggal datang<input name="arr" type="date" value="{{ old('arr', $biz) }}" @if($walk) readonly @endif required></label>
      <label class="fld">Tanggal pergi<input name="dep" type="date" value="{{ old('dep', \App\Support\Fmt::addDays($biz, 1)) }}" required></label>
      <label class="fld">Tipe kamar<select name="type">
        @foreach ($types as $code => $t)<option value="{{ $code }}" @selected(old('type') === $code)>{{ $t->name }} · {{ rp($t->rate) }}</option>@endforeach
      </select></label>
      <label class="fld">Sumber<select name="source">
        @foreach ($walk ? ['Walk-in'] : \App\Models\Reservation::SOURCES as $s)<option @selected(old('source') === $s)>{{ $s }}</option>@endforeach
      </select></label>
      <label class="fld full">Catatan / permintaan khusus<input name="note" value="{{ old('note') }}" placeholder="mis. lantai atas, bebas asap rokok"></label>
    </div>
    @include('partials.inline-err')
  </div>
  <div class="modal-f"><a class="btn" href="{{ close_url() }}">Batal</a><button class="btn pri" type="submit">{{ $walk ? 'Simpan & check-in' : 'Simpan reservasi' }}</button></div>
</form>
