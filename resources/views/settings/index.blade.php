@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
@php $h = app(\App\Services\Hotel::class); @endphp
<div class="head"><div><h1 class="serif">Pengaturan</h1><p>Atur identitas hotel praktik, pajak, tarif kamar, PIN guru, dan daftar siswa.</p></div></div>
@if ($errors->has('msg'))<div class="note warn">{{ ic('warn') }}<span>{{ $errors->first('msg') }}</span></div>@endif
<div class="grid2">
  <form class="card" method="post" action="{{ route('setting.save') }}">
    @csrf
    <div class="card-h"><h2>Hotel &amp; tarif</h2></div>
    <div class="card-b form">
      <label class="fld">Nama hotel praktik<input name="hotel" value="{{ $h->name() }}" required></label>
      <label class="fld">Kelas<input name="cls" value="{{ $h->cls() }}" required></label>
      <label class="fld">Pajak &amp; layanan (%)<input name="tax" type="number" min="0" max="50" value="{{ $h->tax() }}" required></label>
      <label class="fld">PIN guru baru<input name="pin" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah"></label>
      @foreach ($types as $code => $t)
        <label class="fld">Tarif {{ $t->name }}<input name="rate_{{ $code }}" inputmode="numeric" value="{{ $t->rate }}"></label>
      @endforeach
      <div class="full" style="display:flex;justify-content:flex-end"><button class="btn pri" type="submit">Simpan pengaturan</button></div>
    </div>
  </form>
  <form class="card" method="post" action="{{ route('setting.students') }}">
    @csrf
    <div class="card-h"><h2>Daftar siswa</h2></div>
    <div class="card-b" style="display:flex;flex-direction:column;gap:12px">
      <label class="fld">Satu nama per baris<textarea name="list" rows="10">{{ $students }}</textarea></label>
      <div style="display:flex;justify-content:flex-end"><button class="btn pri" type="submit">Simpan daftar siswa</button></div>
    </div>
  </form>
</div>
@endsection
