<p>{{ $this->q }}|{{ $this->page }}</p>
<button data-on-click="search('pretzel')">pretzel</button>
<button data-on-click="next">next</button>
<p>tags: {{ implode(',', $this->tags) }}</p>
<button data-on-click="tag('drama')">drama</button>
