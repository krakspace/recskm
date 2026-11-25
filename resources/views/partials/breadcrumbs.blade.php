<nav class="page-breadcrumbs" aria-label="Breadcrumb">
    <ol>
        @foreach ($breadcrumbs as $crumb)
            <li>
                @if ($loop->last)
                    <span aria-current="page">{{ $crumb['label'] }}</span>
                @elseif (isset($crumb['route']))
                    <a href="{{ route($crumb['route']) }}">{{ $crumb['label'] }}</a>
                @else
                    <span>{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
