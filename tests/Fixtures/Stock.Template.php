@foreach ($this->rows() as $row)
<button data-on-click="remove($row)">{{ $row->name }}</button>
@endforeach
