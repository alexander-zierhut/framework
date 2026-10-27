@extends($layout)

@section("content")
@include("magic/_nav")
<div class="container pb-4">
    <h1>Revenue report</h1>
    <p class="text-muted">This is a full page. The dashboard embeds only its chart fragment.</p>
    @fragment("chart")
    <div class="card" data-test="report-chart">
        <div class="card-header"><strong>Revenue per order</strong> <span class="text-muted small">(fragment "chart" of /magic/report)</span></div>
        <div class="card-body">
            @foreach($orders as $order)
                <div class="d-flex align-items-center mb-2">
                    <div style="width:110px" class="small text-truncate">{{ $order["customer"] }}</div>
                    <div class="flex-grow-1"><div class="bg-{{ $order['shipped'] ? 'success' : 'warning' }}" style="height:18px;width:{{ round($order['total'] / $max * 100) }}%;border-radius:3px"></div></div>
                    <div style="width:80px" class="small text-right">{{ number_format($order["total"], 0, ",", ".") }} €</div>
                </div>
            @endforeach
        </div>
    </div>
    @endfragment
    <p class="mt-4 text-muted">Footer of the full report page, not part of the fragment.</p>
</div>
@endsection
