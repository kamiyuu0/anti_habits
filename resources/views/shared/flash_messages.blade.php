@php
    $flashTypes = [
        'notice' => ['alert-success', 'fas fa-check-circle'],
        'success' => ['alert-success', 'fas fa-check-circle'],
        'alert' => ['alert-error', 'fas fa-times-circle'],
        'error' => ['alert-error', 'fas fa-times-circle'],
        'warning' => ['alert-warning', 'fas fa-exclamation-triangle'],
        'info' => ['alert-info', 'fas fa-info-circle'],
    ];
@endphp
@foreach ($flashTypes as $type => [$alertClass, $iconClass])
  @if (session()->has($type))
    <div class="container mx-auto px-4 py-2">
        <div class="alert {{ $alertClass }} shadow-lg flex items-center">
          <i class="{{ $iconClass }} shrink-0 text-lg mr-3"></i>
          <span>{{ session($type) }}</span>
        </div>
    </div>
  @endif
@endforeach
