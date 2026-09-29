<table class="tbl"><tbody>
@foreach ($o->items as $i)
  <tr><td>{{ $i['qty'] }}× {{ $i['name'] }}</td><td class="num">{{ rp($i['price'] * $i['qty']) }}</td></tr>
@endforeach
<tr><td>Pajak &amp; layanan {{ $taxPct }}%</td><td class="num">{{ rp($o->tax) }}</td></tr>
<tr><td><b>Total</b></td><td class="num"><b>{{ rp($o->total) }}</b></td></tr>
</tbody></table>
<div class="note info">{{ ic('info') }}<span>{{ $o->room_no ? 'Dibebankan ke folio kamar '.$o->room_no.'. Tamu cukup tanda tangan bill.' : 'Dibayar dengan '.$o->payment.'.' }}</span></div>
