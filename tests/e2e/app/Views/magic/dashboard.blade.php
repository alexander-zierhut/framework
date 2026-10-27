@extends($layout)

@section("content")
@include("magic/_nav")
<div class="container pb-4" data-test="magic-dashboard">
    <x-zubzet::island src="/magic/greeting" refresh-on="profile.saved" class="mb-4">
        <div class="placeholder-glow"><h2>&nbsp;</h2></div>
    </x-zubzet::island>

    {{-- polled every 3s, 304 when unchanged, instantly refreshed when an order changes --}}
    <x-zubzet::island src="/magic/stats" every="3s" refresh-on="orders.changed" live class="mb-4" data-test="island-stats">
        <div class="text-muted">Loading numbers…</div>
    </x-zubzet::island>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <x-zubzet::island src="/magic/orders" refresh-on="orders.changed" data-test="island-orders">
                <div class="text-muted">Loading orders…</div>
            </x-zubzet::island>
        </div>
        <div class="col-lg-4">
            <x-zubzet::island src="/magic/profile" class="mb-4" data-test="island-profile"/>
            <x-zubzet::island src="/magic/secret" class="mb-4" data-test="island-secret">
                <div class="card card-body text-muted"><i class="fas fa-lock"></i> Admins only</div>
            </x-zubzet::island>
        </div>
    </div>

    {{-- only the @fragment('chart') of the full /magic/report page, loaded when scrolled into view --}}
    <x-zubzet::island src="/magic/report" fragment="chart" lazy refresh-on="orders.changed" data-test="island-report">
        <div class="card card-body text-muted">The report loads when you scroll here…</div>
    </x-zubzet::island>






    
    {{-- demo only: a tiny inspector listening to the island events --}}
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between"><strong>Network inspector</strong> <a href="{{ $opt["root"] }}magic/reset">reset demo</a></div>
        <ul class="list-group list-group-flush small text-monospace" id="inspector" style="max-height:180px;overflow:auto"></ul>
    </div>
    <script>
        document.addEventListener("z:island:response", function(e) {
            var r = e.detail.response, li = document.createElement("li");
            var note = r.status === 304 ? "unchanged, DOM untouched" : (r.status === 200 ? "swapped" : (r.headers.get("Z-Island-Auth") ? "needs login, nothing injected" : ""));
            li.className = "list-group-item py-1" + (r.status === 304 ? " text-muted" : "");
            li.textContent = new Date().toLocaleTimeString() + "  GET " + new URL(r.url).pathname + (e.detail.el.dataset.zFragment ? " #" + e.detail.el.dataset.zFragment : "") + "  →  " + r.status + "  " + note;
            var list = document.getElementById("inspector");
            list.prepend(li);
            while (list.children.length > 40) list.lastChild.remove();
        });
    </script>
</div>
@endsection
