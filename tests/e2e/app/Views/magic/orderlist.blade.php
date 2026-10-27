@extends($layout)

@section("content")
@php
    $link = function(array $change) use ($page, $sort, $dir, $q) {
        return "?" . http_build_query(array_merge(["page" => $page, "sort" => $sort, "dir" => $dir, "q" => $q], $change));
    };
    $sortLink = fn($col) => $link(["sort" => $col, "dir" => ($sort === $col && $dir === "asc") ? "desc" : "asc", "page" => 1]);
    $arrow = fn($col) => $sort === $col ? ($dir === "asc" ? " ▲" : " ▼") : "";
@endphp
<div class="card">
    <div class="card-body pb-2">
        <form method="get" action="{{ $opt['root'] }}magic/orderlist" class="form-inline">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm mr-2" placeholder="Search customer" data-test="list-search">
            <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="dir" value="{{ $dir }}">
            <button class="btn btn-sm btn-primary">Search</button>
            <span class="ml-auto small text-muted">{{ $total }} orders · page {{ $page }} of {{ $pages }}</span>
        </form>
    </div>
    <table class="table table-sm table-hover mb-0">
        <thead><tr>
            <th><a href="{{ $opt['root'] }}magic/orderlist{{ $sortLink('id') }}">#{{ $arrow('id') }}</a></th>
            <th><a href="{{ $opt['root'] }}magic/orderlist{{ $sortLink('customer') }}">Customer{{ $arrow('customer') }}</a></th>
            <th class="text-right"><a href="{{ $opt['root'] }}magic/orderlist{{ $sortLink('total') }}">Total{{ $arrow('total') }}</a></th>
        </tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr><td>{{ $row["id"] }}</td><td>{{ $row["customer"] }}</td><td class="text-right">{{ number_format($row["total"], 0, ",", ".") }} €</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-footer">
        <ul class="pagination pagination-sm mb-0" data-test="list-pager">
            <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}"><a class="page-link" href="{{ $opt['root'] }}magic/orderlist{{ $link(['page' => $page - 1]) }}">‹</a></li>
            @for($p = 1; $p <= $pages; $p++)
                <li class="page-item {{ $p === $page ? 'active' : '' }}"><a class="page-link" href="{{ $opt['root'] }}magic/orderlist{{ $link(['page' => $p]) }}">{{ $p }}</a></li>
            @endfor
            <li class="page-item {{ $page >= $pages ? 'disabled' : '' }}"><a class="page-link" href="{{ $opt['root'] }}magic/orderlist{{ $link(['page' => $page + 1]) }}">›</a></li>
        </ul>
    </div>
</div>
@endsection
