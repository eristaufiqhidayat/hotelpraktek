<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Folio {{ $r->code }} · {{ $hotel->name() }}</title>
<style>
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;font-size:13px;color:#000;margin:24px;max-width:780px}
h2{margin:0 0 2px}h3{margin:18px 0 6px}p{margin:4px 0}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border-bottom:1px solid #ccc;padding:6px;text-align:left}.r{text-align:right}
.head{display:flex;gap:14px;align-items:center}.head img{width:60px;height:60px;border-radius:50%}
@media print{.noprint{display:none}}
</style>
</head>
<body onload="window.print()">
<div class="head"><img src="{{ asset('img/logo-smk.jpg') }}" alt=""><div><h2>{{ $hotel->name() }}</h2><p>{{ $hotel->school() }} · Dokumen latihan</p></div></div>
<h3>Folio / Invoice · {{ $r->code }}</h3>
<p>Tamu: <b>{{ $r->guest }}</b> · Kamar {{ $r->room_no }} ({{ $r->type->name }}) · {{ fdate($r->arrival) }} – {{ fdate($r->departure) }}</p>
<table><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Departemen</th><th class="r">Jumlah</th></tr></thead><tbody>
@foreach ($r->folio as $l)
  <tr><td>{{ fdate($l->date) }}</td><td>{{ $l->description }}</td><td>{{ $l->dept }}</td><td class="r">{{ rp($l->amount) }}</td></tr>
@endforeach
<tr><td></td><td><b>Saldo</b></td><td></td><td class="r"><b>{{ rp($r->balance()) }}</b></td></tr>
</tbody></table>
<p style="margin-top:14px">Dicetak oleh {{ $hotel->who() }} · {{ now()->format('d/m/Y H.i') }}</p>
<p class="noprint"><button onclick="window.print()">Cetak</button> <button onclick="window.close()">Tutup</button></p>
</body>
</html>
