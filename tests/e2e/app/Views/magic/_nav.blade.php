<nav class="navbar navbar-expand navbar-dark bg-dark mb-4" data-test="magic-nav">
    <a class="navbar-brand" href="{{ $opt['root'] }}magic/dashboard">✨ Magic</a>
    <div class="navbar-nav">
        <a class="nav-link" href="{{ $opt['root'] }}magic/dashboard">Dashboard</a>
        <a class="nav-link" href="{{ $opt['root'] }}magic/list">Paginated list</a>
        <a class="nav-link" href="{{ $opt['root'] }}magic/report">Full report</a>
        <a class="nav-link" href="{{ $opt['root'] }}magic/lab">Scope lab</a>
    </div>
    <span class="navbar-text ml-auto small" id="nav-proof"></span>
</nav>
<script>
    // Survives boosted navigation, resets on a real page load
    window.__bootedAt = window.__bootedAt || new Date().toLocaleTimeString();
    window.__views = (window.__views || 0) + 1;
    document.getElementById("nav-proof").textContent = "JS alive since " + window.__bootedAt + " · " + window.__views + " page view(s) without reload";
</script>
