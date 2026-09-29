{{-- $items: [[key, label, count?]], $cur, $url: fn(key) => url --}}
<div class="tabs" role="tablist">
  @foreach ($items as $t)
    <a role="tab" aria-selected="{{ $cur === $t[0] ? 'true' : 'false' }}" class="{{ $cur === $t[0] ? 'on' : '' }}" href="{{ $url($t[0]) }}">{{ $t[1] }}@isset($t[2])<span class="n">{{ $t[2] }}</span>@endisset</a>
  @endforeach
</div>
