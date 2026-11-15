{{-- Paginación de SIGEs (copia de la de Bootstrap 5 de Laravel): en español y sin "Showing x to y of z results",
     porque <x-por-pagina> ya muestra el total. En celular, solo "Anterior" y "Siguiente". --}}
@if ($paginator->hasPages())
    <nav class="d-flex justify-items-center justify-content-between" aria-label="Paginación">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">&lsaquo; Anterior</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Anterior</a></li>
                @endif
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span></li>
                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente &rsaquo;</a></li>
                @else
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">Siguiente &rsaquo;</span></li>
                @endif
            </ul>
        </div>

        <div class="d-none d-sm-flex">
            <ul class="pagination">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="Anterior"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&lsaquo;</a></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">&rsaquo;</a></li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
                @endif
            </ul>
        </div>
    </nav>
@endif
