@extends($layout)

@section("content")
<div class="row">
    @foreach([["Orders", $count, "fa-receipt", "primary"], ["Open", $open, "fa-box-open", "warning"], ["Revenue", number_format($revenue, 0, ",", ".") . " €", "fa-euro-sign", "success"], ["Server minute", $minute, "fa-clock", "secondary"]] as [$label, $value, $icon, $color])
        <div class="col-6 col-md-3 mb-2">
            <div class="card border-{{ $color }} h-100">
                <div class="card-body py-3">
                    <div class="text-muted small"><i class="fas {{ $icon }}"></i> {{ $label }}</div>
                    <div class="h3 mb-0 text-{{ $color }}" data-test="stat-{{ strtolower(str_replace(" ", "-", $label)) }}">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
