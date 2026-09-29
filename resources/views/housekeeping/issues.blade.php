@if ($issues->isNotEmpty())
  <div style="margin-top:14px"><b style="font-size:13px">Laporan kerusakan terbuka</b>
    @foreach ($issues as $i)
      <div style="padding:8px 0;border-bottom:1px solid var(--line-2);font-size:13px"><b>Kamar {{ $i->room_no }}</b> · {{ $i->text }}<span class="sub" style="display:block;color:var(--muted)">{{ fshort($i->date) }} · {{ $i->reported_by }}</span></div>
    @endforeach
  </div>
@endif
