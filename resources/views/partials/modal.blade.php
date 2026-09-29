{{-- Modal dibuka lewat query string; hasil transaksi (struk) lewat flash session. --}}
@if ($modal)
  <div class="overlay" data-close="{{ close_url() }}">
    <div class="modal {{ in_array($modal, ['checkin', 'folio', 'checkout']) ? 'wide' : '' }}" role="dialog" aria-modal="true" aria-labelledby="mtitle">
      @include('modals.'.($modal === 'walkin' ? 'newres' : $modal))
    </div>
  </div>
@elseif ($receipt)
  <div class="overlay" data-close="{{ close_url() }}">
    <div class="modal {{ ! empty($receipt['wide']) ? 'wide' : '' }}" role="dialog" aria-modal="true" aria-labelledby="mtitle">
      @include('partials.mhead', ['title' => $receipt['title'], 'sub' => $receipt['sub'] ?? ''])
      <div class="modal-b">{!! $receipt['body'] !!}</div>
      <div class="modal-f">
        @if (! empty($receipt['print']))
          <a class="btn" href="{{ $receipt['print'] }}" target="_blank">{{ ic('print', 16) }}Cetak</a>
        @endif
        <a class="btn pri" href="{{ close_url() }}" data-dismiss>Selesai</a>
      </div>
    </div>
  </div>
@endif
