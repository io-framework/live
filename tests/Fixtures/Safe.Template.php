<button data-on-click="toggle">toggle</button>
@if ($this->open)
<button data-on-click="wipe">wipe</button>
@endif
<output>{{ $this->wiped }}</output>
