@if ($list->isEmpty())
  <div class="empty">{{ $kind === 'arr' ? 'Semua tamu hari ini sudah check-in.' : 'Tidak ada tamu yang harus check-out.' }}</div>
@else
  <div class="tbl-wrap"><table class="tbl"><tbody>
  @foreach ($list as $r)
    <tr>
      <td><b>{{ $r->guest }}</b><span class="sub">{{ $r->code }} · {{ $r->type->name }}{{ $r->room_no ? ' · Kamar '.$r->room_no : '' }}</span></td>
      <td>{{ $r->nights }} malam</td>
      <td class="num">
        @if ($kind === 'arr')<a class="btn sm pri" href="{{ modal_url('checkin', $r->id) }}">Check-in</a>
        @else<a class="btn sm" href="{{ modal_url('checkout', $r->id) }}">Check-out</a>@endif
      </td>
    </tr>
  @endforeach
  </tbody></table></div>
@endif
