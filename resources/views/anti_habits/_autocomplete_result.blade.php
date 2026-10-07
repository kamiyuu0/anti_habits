@foreach ($antiHabits as $antiHabit)
  <li class="list-group-item px-4 py-2 hover:bg-base-200 cursor-pointer" role="option" data-autocomplete-value="{{ $antiHabit->title }}" data-autocomplete-label="{{ $antiHabit->title }}">
    {{ $antiHabit->title }}
  </li>
@endforeach
