@extends($layout)

@section("content")
@include("magic/_nav")
<div class="container">
    <h2>Orders</h2>
    <p class="text-muted">The table is <code>&lt;x-zubzet::island src="/magic/orderlist" nav/&gt;</code>. Its links and search form are plain HTML with ?page=, ?sort= and ?q=.</p>
    <x-zubzet::island src="/magic/orderlist" nav data-test="island-list">
        <div class="text-muted">Loading…</div>
    </x-zubzet::island>
</div>
@endsection
