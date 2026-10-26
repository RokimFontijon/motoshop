{{-- Simple pager used with: {{ $items->links('partials.pagination') }} --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-outline btn-sm disabled">&laquo; Previous</span>
        @else
            <a class="btn btn-outline btn-sm" href="{{ $paginator->previousPageUrl() }}">&laquo; Previous</a>
        @endif

        <span class="pager-info">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            &middot; {{ $paginator->total() }} records
        </span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-outline btn-sm" href="{{ $paginator->nextPageUrl() }}">Next &raquo;</a>
        @else
            <span class="btn btn-outline btn-sm disabled">Next &raquo;</span>
        @endif
    </nav>
@endif
