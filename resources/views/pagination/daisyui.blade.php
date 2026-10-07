@if ($paginator->hasPages())
  <div class="join text-xs mt-8 mx-auto flex justify-center">
    <nav class="pagination" role="navigation" aria-label="pager">
      @unless ($paginator->onFirstPage())
        <span class="prev">
          <a href="{{ $paginator->previousPageUrl() }}" class="join-item btn" rel="prev">{{ __('pagination.previous') }}</a>
        </span>
      @endunless

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="join-item btn btn-disabled">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            <a href="{{ $url }}" class="join-item btn {{ $page == $paginator->currentPage() ? 'btn-active' : '' }}">{{ $page }}</a>
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <span class="next">
          <a href="{{ $paginator->nextPageUrl() }}" class="join-item btn" rel="next">{{ __('pagination.next') }}</a>
        </span>
      @endif
    </nav>
  </div>
@endif
