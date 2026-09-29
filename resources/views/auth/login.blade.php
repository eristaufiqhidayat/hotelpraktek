<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $hotel->name() }} · Sistem Hotel Pendidikan</title>
<meta name="description" content="Sistem hotel pendidikan untuk praktik siswa {{ $hotel->school() }}.">
<meta name="robots" content="noindex">
<link rel="icon" href="{{ asset('img/favicon.jpg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
@php $guru = old('role') === 'guru'; @endphp
<div class="login">
  <section class="hero">
    <div>
      <img class="logo" src="{{ asset('img/logo-smk.jpg') }}" alt="Logo SMK Keluarga Widuri">
      <h1 class="serif">{{ $hotel->name() }}</h1>
      <p>Sistem hotel untuk praktik siswa Perhotelan dan Kuliner {{ $hotel->school() }}. Siswa belajar alur kerja hotel sungguhan, dari reservasi sampai night audit, sementara guru memantau dan menilai setiap transaksi.</p>
    </div>
    <div class="by"><img src="{{ asset('img/logo-peacock.png') }}" alt="">Disiapkan oleh Peacock Integrasi Indonesia · Semua data adalah contoh</div>
  </section>
  <section class="panel">
    <form class="box" method="post" action="{{ route('login.do') }}">
      @csrf
      <h2 class="serif">Masuk ke hotel praktik</h2>
      @if ($errors->has('msg'))<div class="err" role="alert">{{ $errors->first('msg') }}</div>@endif
      <fieldset style="border:none;margin:0;padding:0"><legend class="sr">Masuk sebagai</legend>
        <div class="role-pick">
          <label><input type="radio" name="role" value="siswa" @checked(! $guru)><span><b>Siswa</b><small>Mengoperasikan hotel</small></span></label>
          <label><input type="radio" name="role" value="guru" @checked($guru)><span><b>Guru</b><small>Memantau &amp; menilai</small></span></label>
        </div>
      </fieldset>
      <div id="lg-siswa" class="{{ $guru ? 'hidden' : '' }}" style="display:flex;flex-direction:column;gap:14px">
        <label class="fld">Nama siswa<select name="student">
          @foreach ($students as $s)<option value="{{ $s->id }}" @selected(old('student') == $s->id)>{{ $s->name }}</option>@endforeach
        </select></label>
        <label class="fld">Bertugas sebagai<select name="dept">
          @foreach ($depts as $d)<option @selected(old('dept') === $d)>{{ $d }}</option>@endforeach
        </select></label>
      </div>
      <div id="lg-guru" class="{{ $guru ? '' : 'hidden' }}" style="display:flex;flex-direction:column;gap:14px">
        <label class="fld">Nama guru<input name="guru" value="{{ old('guru', 'Guru Perhotelan') }}" autocomplete="off"></label>
        <label class="fld">PIN guru<input name="pin" type="password" autocomplete="current-password" placeholder="PIN dari admin sekolah"></label>
      </div>
      <button class="btn pri lg" type="submit">Masuk</button>
      <div class="note info">{{ ic('info') }}<span>Ini adalah <b>mode latihan</b>. Semua transaksi tercatat untuk penilaian, dan guru bisa mengembalikan hotel ke kondisi awal kapan saja.</span></div>
    </form>
  </section>
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
