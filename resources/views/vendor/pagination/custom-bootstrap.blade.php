@if ($paginator->hasPages())
    <nav>
        <ul class="pagination justify-content-center">
            {{-- First Page Link --}}
            <li class="page-item{{ $paginator->onFirstPage() ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->url(1) }}" tabindex="-1">&laquo;&laquo;</a>
            </li>

            {{-- Previous Page Link --}}
            <li class="page-item{{ $paginator->onFirstPage() ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" tabindex="-1">&laquo;</a>
            </li>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item{{ $page == $paginator->currentPage() ? ' active' : '' }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            <li class="page-item{{ !$paginator->hasMorePages() ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}">&raquo;</a>
            </li>

            {{-- Last Page Link --}}
            <li class="page-item{{ !$paginator->hasMorePages() ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
@endif
