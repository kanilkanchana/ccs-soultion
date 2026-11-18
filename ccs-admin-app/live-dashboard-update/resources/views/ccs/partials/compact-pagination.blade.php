@if($paginator->hasPages())
<nav class="ccs-compact-pagination" aria-label="{{ __('Page navigation') }}">
@if($paginator->onFirstPage())<span aria-disabled="true">{{ __('pagination.previous') }}</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a>@endif
<span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
@if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a>@else<span aria-disabled="true">{{ __('pagination.next') }}</span>@endif
</nav>
@endif
