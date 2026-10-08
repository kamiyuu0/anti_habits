@foreach (\App\Enums\ReactionKind::cases() as $kind)
  @include('shared.reaction_button', ['antiHabit' => $antiHabit, 'kind' => $kind])
@endforeach
