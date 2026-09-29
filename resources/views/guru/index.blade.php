@extends('layouts.app')
@section('title', 'Panel Guru')
@section('content')
@php $h = app(\App\Services\Hotel::class); @endphp
<div class="head">
  <div><h1 class="serif">Panel Guru</h1><p>{{ $h->cls() }} · pantau aktivitas, kirim skenario latihan, dan nilai siswa.</p></div>
  <div class="acts">
    <a class="btn" href="{{ route('guru.export') }}">Unduh log (CSV)</a>
    <form method="post" action="{{ route('guru.reset') }}" data-confirm="Kembalikan hotel ke data awal? Semua transaksi latihan akan dihapus.">@csrf<button class="btn danger" type="submit">Reset hotel ke data awal</button></form>
  </div>
</div>
<div class="kpis">
  @include('partials.kpi', ['l' => 'Siswa aktif', 'v' => $active.' / '.$students->count(), 's' => 'mencatat transaksi'])
  @include('partials.kpi', ['l' => 'Transaksi siswa', 'v' => $total - $errs, 's' => 'berhasil'])
  @include('partials.kpi', ['l' => 'Kesalahan tercatat', 'v' => $errs, 's' => 'ditolak sistem'])
  @include('partials.kpi', ['l' => 'Ketepatan kelas', 'v' => $total ? round(($total - $errs) / $total * 100).'%' : '–', 's' => 'transaksi benar'])
</div>
<div class="grid-side">
  <section class="card">
    <div class="card-h"><h2>Rekap per siswa</h2><span style="color:var(--muted);font-size:12px">Nilai praktik disarankan dari ketepatan</span></div>
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>Siswa</th><th>Peran terakhir</th><th class="num">Benar</th><th class="num">Salah</th><th class="num">Ketepatan</th><th class="num">Saran nilai</th><th>Aktivitas terakhir</th></tr></thead>
      <tbody>
      @foreach ($stats as $s)
        <tr>
          <td><div style="display:flex;align-items:center;gap:8px"><span class="avatar" style="width:30px;height:30px;font-size:11px">{{ \App\Support\Fmt::initials($s->name) }}</span><b>{{ $s->name }}</b></div></td>
          <td>{{ $s->role ?? '–' }}</td>
          <td class="num">{{ $s->ok }}</td>
          <td class="num" style="color:{{ $s->err ? 'var(--red)' : 'inherit' }}">{{ $s->err }}</td>
          <td class="num">{{ $s->acc === null ? '–' : $s->acc.'%' }}</td>
          <td class="num"><b>{{ $s->nilai ?? '–' }}</b></td>
          <td>@if($s->last){{ $s->last->action }}<span class="sub">{{ fshort($s->last->biz_date) }} · {{ \App\Support\Fmt::clock($s->last->created_at) }}</span>@else<span class="sub">Belum ada</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
  <aside class="card">
    <div class="card-h"><h2>{{ ic('bolt', 16) }} Kirim skenario</h2></div>
    <div class="card-b" style="display:flex;flex-direction:column;gap:10px">
      @foreach ($scenarios as $key => [$title, $desc])
        <form method="post" action="{{ route('guru.scenario') }}" style="display:flex">@csrf<input type="hidden" name="s" value="{{ $key }}">
          <button class="btn" type="submit" style="flex:1;justify-content:flex-start;height:auto;padding:10px 14px;text-align:left;white-space:normal"><span style="display:flex;flex-direction:column;gap:2px"><b>{{ $title }}</b><small style="color:var(--muted);font-weight:400">{{ $desc }}</small></span></button>
        </form>
      @endforeach
    </div>
  </aside>
</div>
<section class="card">
  <div class="card-h"><h2>Log aktivitas</h2>
    <form method="get" action="{{ route('guru') }}"><label class="sr" for="logu">Filter siswa</label>
      <select id="logu" name="u" class="search" data-autosubmit>
        <option value="">Semua siswa</option>
        @foreach ($students as $s)<option @selected($u === $s->name)>{{ $s->name }}</option>@endforeach
      </select>
    </form>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Detail</th><th>Hasil</th></tr></thead>
    <tbody>
    @forelse ($feed as $l)
      <tr><td>{{ fshort($l->biz_date) }}<span class="sub">{{ \App\Support\Fmt::clock($l->created_at) }}</span></td><td><b>{{ $l->user_name }}</b><span class="sub">{{ $l->role }}</span></td><td>{{ $l->action }}</td><td>{{ $l->detail }}</td>
        <td>@if($l->ok)<span class="chip c-ok">Benar</span>@else<span class="chip c-err">Salah</span>@endif</td></tr>
    @empty
      <tr><td colspan="5" class="empty">Belum ada aktivitas.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</section>
@endsection
