@include('partials.mhead', ['title' => 'Laporan kerusakan · Kamar '.$room->no, 'sub' => $room->type->name])
<form method="post" action="{{ route('hk.issue', $room) }}">
  @csrf
  <div class="modal-b">
    <label class="fld">Kerusakan yang ditemukan<textarea name="text" placeholder="mis. keran wastafel bocor">{{ old('text') }}</textarea></label>
    <label style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="block" value="1" style="width:18px;height:18px;accent-color:var(--pri)" @disabled($occupied) @checked(! $occupied)>Kamar tidak bisa dijual (Out of Order)</label>
    @if ($occupied)
      <div class="note info">{{ ic('info') }}<span>Kamar sedang ditempati tamu, jadi hanya dibuat laporan perbaikan.</span></div>
    @endif
    @include('partials.inline-err')
  </div>
  <div class="modal-f"><a class="btn" href="{{ close_url() }}">Batal</a><button class="btn pri" type="submit">Kirim laporan</button></div>
</form>
