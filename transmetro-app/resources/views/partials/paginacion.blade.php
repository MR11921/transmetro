@if ($paginator->hasPages())
<nav aria-label="Paginación"><ul class="pagination">
    @if (!$paginator->onFirstPage())<li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a></li>@endif
    @foreach ($elements as $element)
        @if (is_string($element))<li><span>{{ $element }}</span></li>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                <li class="{{ $page == $paginator->currentPage() ? 'active' : '' }}">@if ($page == $paginator->currentPage())<span>{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif</li>
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())<li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a></li>@endif
</ul></nav>
@endif
