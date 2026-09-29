<div class="tbl-wrap folio"><table class="tbl"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Departemen</th><th class="num">Jumlah</th></tr></thead><tbody>
@forelse ($r->folio as $l)
  <tr><td>{{ fshort($l->date) }}</td><td>{{ $l->description }}<span class="sub">{{ $l->created_by }}</span></td><td>{{ $l->dept }}</td><td class="num {{ $l->amount < 0 ? 'amt-neg' : '' }}">{{ rp($l->amount) }}</td></tr>
@empty
  <tr><td colspan="4" class="empty">Belum ada transaksi.</td></tr>
@endforelse
</tbody></table></div>
