<div class="kv">
  <div><span>Okupansi</span><b>{{ pct($st['pct']) }}</b></div><div><span>Kamar terjual</span><b>{{ $st['occ'] }} / {{ $st['avail'] }}</b></div>
  <div><span>ADR</span><b>{{ rp($st['adr']) }}</b></div><div><span>RevPAR</span><b>{{ rp($st['revpar']) }}</b></div>
  <div><span>Pendapatan kamar</span><b>{{ rp($st['roomRev']) }}</b></div><div><span>Pendapatan F&amp;B</span><b>{{ rp($st['fnb']) }}</b></div>
  <div><span>Lainnya</span><b>{{ rp($st['other']) }}</b></div><div><span>No-show</span><b>{{ $noshow }}</b></div>
</div>
