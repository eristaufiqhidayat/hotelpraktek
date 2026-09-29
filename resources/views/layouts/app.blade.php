@php
    $user = $hotel->user();
    $isGuru = $hotel->isGuru();
    $nav = [
        ['sec', 'Operasional hotel'],
        ['dashboard', 'Beranda', 'home'], ['res.index', 'Reservasi', 'cal'], ['fo.index', 'Front Office', 'desk'],
        ['hk.index', 'Housekeeping', 'bed'], ['pos.index', 'Restoran (POS)', 'cup'], ['audit.index', 'Night Audit', 'moon'], ['report', 'Laporan', 'chart'],
        ['sec', 'Guru', 'guru'], ['guru', 'Panel Guru', 'cap', 'guru'], ['setting', 'Pengaturan', 'cog', 'guru'],
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Beranda') · {{ $hotel->name() }}</title>
<meta name="robots" content="noindex">
<link rel="icon" href="{{ asset('img/favicon.jpg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><img src="{{ asset('img/logo-smk.jpg') }}" alt="Logo SMK Keluarga Widuri"><div><b>{{ $hotel->name() }}</b><small>{{ strtoupper($hotel->school()) }}</small></div></div>
    <nav class="nav" aria-label="Menu utama">
      @foreach ($nav as $n)
        @continue(($n[3] ?? null) === 'guru' && ! $isGuru)
        @if ($n[0] === 'sec')
          <div class="sec">{{ $n[1] }}</div>
        @else
          @php $on = request()->routeIs($n[0]); @endphp
          <a href="{{ route($n[0]) }}" class="{{ $on ? 'on' : '' }}" @if($on) aria-current="page" @endif>{{ ic($n[2]) }}{{ $n[1] }}</a>
        @endif
      @endforeach
    </nav>
    <div class="foot"><img src="{{ asset('img/logo-peacock.png') }}" alt="">Peacock Integrasi Indonesia</div>
  </aside>
  <div class="main">
    <header class="top">
      <div class="date"><small>Tanggal hotel</small><b>{{ fdate($hotel->bizDate(), true) }}</b></div>
      <span class="pill train">MODE LATIHAN</span>
      <div class="user">
        <div class="avatar">{{ \App\Support\Fmt::initials($user['name']) }}</div>
        <span><b>{{ $user['name'] }}</b><small>{{ $isGuru ? 'Guru · '.$hotel->cls() : $user['dept'].' · '.$hotel->cls() }}</small></span>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn sm" type="submit">{{ ic('out', 16) }}Ganti pengguna</button></form>
      </div>
    </header>
    <main class="content" id="main">
      @yield('content')
    </main>
  </div>
</div>
@include('partials.modal')
<div id="toast" aria-live="polite">
  @if (session('toast'))
    <div class="toast {{ session('toast')[1] ?? '' }}" role="{{ (session('toast')[1] ?? '') === 'err' ? 'alert' : 'status' }}">{{ session('toast')[0] }}</div>
  @endif
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
