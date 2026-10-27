@extends($layout)

@section("content")
@include("magic/_nav")
<div class="container">
    <h2>Scope lab</h2>
    <p class="text-muted">This view's script uses top-level <code>const</code>/<code>let</code>/<code>class</code>, a <code>setInterval</code>, a native <code>document</code> listener and a jQuery <code>$(document).on</code>. Navigate away and back a few times: without scopes the second visit throws "already declared" and every visit adds another timer and listener.</p>
    <table class="table table-bordered w-auto" data-test="lab">
        <tr><th>Script runs (page views)</th><td id="lab-runs">-</td></tr>
        <tr><th>Live intervals right now</th><td id="lab-intervals" data-test="lab-intervals">-</td></tr>
        <tr><th>Native document click handlers firing per click</th><td id="lab-native" data-test="lab-native">click anywhere</td></tr>
        <tr><th>jQuery document click handlers firing per click</th><td id="lab-jquery" data-test="lab-jquery">click anywhere</td></tr>
        <tr><th>Top-level const visible on window</th><td id="lab-const" data-test="lab-const">-</td></tr>
    </table>
    <button class="btn btn-outline-primary" onclick="labGreeter.hello()" data-test="lab-inline">onclick uses a top-level const</button>
    <span id="lab-hello" class="ml-2"></span>
</div>
<script>
    const labStarted = new Date().toLocaleTimeString();
    let labTicks = 0;
    class LabGreeter { hello() { document.getElementById("lab-hello").textContent = "hello from the run started " + labStarted; } }
    const labGreeter = new LabGreeter();

    window.__labRuns = (window.__labRuns || 0) + 1;
    document.getElementById("lab-runs").textContent = window.__labRuns;
    document.getElementById("lab-const").textContent = typeof window.labGreeter === "object" ? "yes" : "pending";

    // Every live interval reports in; more than one means timers piled up
    window.__labLive = window.__labLive || {};
    const labRun = window.__labRuns;
    setInterval(function() {
        window.__labLive[labRun] = Date.now();
        const live = Object.values(window.__labLive).filter(t => Date.now() - t < 1500).length;
        const cell = document.getElementById("lab-intervals");
        if (cell) cell.textContent = live;
    }, 500);

    document.addEventListener("click", function() {
        window.__labNative = (window.__labNative || 0) + 1;
        clearTimeout(window.__labNativeT);
        window.__labNativeT = setTimeout(function() {
            const cell = document.getElementById("lab-native");
            if (cell) cell.textContent = window.__labNative;
            window.__labNative = 0;
        }, 50);
    });

    $(document).on("click", function() {
        window.__labJq = (window.__labJq || 0) + 1;
        clearTimeout(window.__labJqT);
        window.__labJqT = setTimeout(function() {
            const cell = document.getElementById("lab-jquery");
            if (cell) cell.textContent = window.__labJq;
            window.__labJq = 0;
        }, 50);
    });
</script>
@endsection
