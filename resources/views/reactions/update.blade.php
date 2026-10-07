<turbo-stream action="replace" target="reaction-{{ $kind->key() }}-form-for-anti_habit-{{ $antiHabit->id }}">
  <template>@include('shared.reaction_button', ['antiHabit' => $antiHabit, 'kind' => $kind])</template>
</turbo-stream>
