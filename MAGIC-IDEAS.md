# ZubZet Magic Ideas

Source: https://claude.ai/artifact/T1zw92UMA2e68odaAv2KBM

60 ideas for one-tag / one-line features built on the `<x-zubzet::island>` primitive. Score = 40% impact, 35% simplicity, 25% wow (simplicity measured from plain `develop`).

## Building blocks

- **Server fragment contract**: Z-Island request header (Request::header/isIsland/islandFragment), first top-level render drops its layout, single @fragment extraction (404 if missing), weak ETag + 304 via If-None-Match (gzip-suffix tolerant), Vary header, status mapping: 401/403 via checkPermission refuse, 500 on BladeException, 204 + Z-Island-Redirect from rerouteUrl. Starting point from plain develop for anything that fetches a partial page. (95 LOC; `fragment-contract`; src/Rendering/Island.php (claim/release/respond/refuse/redirect/header/html/etagMatches, 70); src/Message/Request.php (header/isIsland/islandFragment 13 + 2 refuse hooks); src/Message/Response.php rerouteUrl hook (1); src/Rendering/CanRenderView.php (9). Counted with a script over line ranges: non-blank lines not starting with //, /*, *, or {{-- (docblocks excluded).)
- **Script scope runner (Z.Scope)**: Re-executes inline scripts of inserted HTML: block-wraps them and exports top-level let/const/class to window, records window/document/body listeners (native + jQuery namespaced) and timers per scope and tears them down, replays DOMContentLoaded/load handlers, runs same-origin <script src> once, scopes document.getElementById to the running container (6 of the LOC). Needed by anything that swaps server HTML that may contain view scripts. (124 LOC; `script-scope`; web/Z.js 'Experimental: scopes' (minus the head-marking MutationObserver, counted under page-navigation))
- **Island client runtime core**: Z.Islands core: sameApp origin/root guard, per-element state, fetch with Z-Island/Z-Island-Fragment/If-None-Match, AbortController, 304 handling, marked-HTML-only swap via template + scope run, polling with jitter and min interval, exponential backoff, 401 reload-once, 204 redirect, typing guard, lazy IntersectionObserver, visibilitychange resume, MutationObserver start/stop, z:island:* events, public refresh/scan/stop API. (194 LOC; `island-runtime`; web/Z.js 'Experimental: islands' (state/load/handle/swap/fail/start/stop/islandsIn/boot/visibility/API, plus shared navIsland/plainClick helpers))
- **Response effect headers (trigger + flash)**: Response::islandTrigger (Z-Island-Trigger, validated event names) and Response::islandFlash (Z-Island-Flash level:text); client handleHeaders on island fetches and on every jQuery ajaxComplete (ZForm, Z.Request), refresh-on trigger dispatch, plain-text toast container. The flash/toast half (~25 LOC) works without islands; the trigger half needs island-runtime. (41 LOC; `response-effects`; web/Z.js handleHeaders/toast/ajaxComplete/trigger+toast API (31); src/Message/Response.php islandTrigger/islandFlash (7); Island::eventNames (3))
- **Island Blade component + Island::describe**: <x-zubzet::island src fragment every lazy nav live refresh-on scripts> markup emitting data-z-* attributes, with server-side validation of src (app-absolute, no dot segments), names, event lists and interval parsing/clamping (2s..1h); invalid input throws in test mode and renders inert otherwise. describe() is reused by action and modal. (45 LOC; `island-component`; src/IncludedComponents/views/components/island.blade.php (3); Island::describe/interval/invalidSrc/invalidName/invalidEvents/inert (42))
- **ZForm-in-island integration + touched refresh**: ZForm.send posts to the enclosing island's src instead of the host page URL; on success inside an island it toasts, refreshes the island (or closes its modal) and calls Z.Islands.touched(), which refreshes every other island showing the same route. (16 LOC; `zform-island`; web/Z.js ZForm.send tweak (9) + touched API (7))
- **Action button**: <x-zubzet::action post data confirm then> renders a button; a delegated click POSTs FormData with Z-Island header, applies effect headers, toasts on error, refreshes the surrounding island and touched routes, disables the button in flight. (22 LOC; `action-button`; web/Z.js action click handler (19); src/IncludedComponents/views/components/action.blade.php (3))
- **Route-in-modal**: <x-zubzet::modal href title size> link (plain link without JS); openModal builds a Bootstrap modal whose body is a nav island on that route, removed on hide; a ZForm save inside closes it. (28 LOC; `modal`; web/Z.js openModal + click handler (25); src/IncludedComponents/views/components/modal.blade.php (3))
- **Nav islands (links and GET forms in place)**: Islands with nav: delegated click on plain-click links and submit of GET forms inside the island re-point data-z-src (same-app only, data-z-native opt-out) and force-load in place: pagination, sorting, filters. (29 LOC; `nav-islands`; web/Z.js navigate() + nav click/submit handlers)
- **Page navigation (boost)**: Opt-in navigation=boost (head meta): intercepts same-app link clicks, fetches the page, falls back to a hard load when head assets differ / non-HTML / error, swaps body and page-owned head scripts/styles, tears down the old page scope and re-runs scripts in a new one, pushState/popstate with scroll restore, progress bar, z:navigate event, fixes jQuery default ajax url. (126 LOC; `page-navigation`; web/Z.js 'Experimental: page navigation' (114) + head-marking MutationObserver in Z.Scope (9); head.blade.php meta (3))
- **Pre-render version/ETag check helper**: $res->notModified(...$versionParts): builds a weak ETag from version parts + user + fragment + path, answers 304 before any query/render runs for island requests, no-op otherwise. Base for cheap polling of expensive views. (25 LOC; `etag-precheck`; estimate: Response.php method + Island ETag helper)
- **Named version counters (bump + auto-trigger)**: Core migration z_version(name, v) with versions()->bump(name) (upsert + islandTrigger) and versions(...) read for ETag parts; lets writes invalidate pollers and refresh listeners without hand-written triggers. (45 LOC; `version-counters`; estimate: migration, small Versions class, helper function)
- **Cross-tab relay (BroadcastChannel)**: Posts every trigger/flash/touched event on BroadcastChannel('z-islands:'+rootPath) and replays received ones locally (loop-guarded by tab id); base for cross-tab refresh, unsaved-change notices, leader-tab polling variants. (25 LOC; `broadcast-relay`; estimate: web/Z.js)
- **Swap pipeline hooks**: Before/after-swap extension points inside island swap (and page navigation render) so focus/caret/scroll preservation, permanent elements, keyed swaps, view transitions, animated counters and change highlights plug in without each re-implementing the swap. (20 LOC; `swap-hooks`; estimate: web/Z.js island swap())
- **ZForm send lifecycle hooks**: before-send/after-send/success/error events plus a dirty flag on ZForm, so busy buttons, upload progress, autosave, unsaved-changes guard, idempotency keys, accessible error focus and Ctrl+S attach in one place. Independent of islands. (25 LOC; `zform-lifecycle`; estimate: web/Z.js ZForm)
- **Client validation probe header**: Z-Validate: <field> request header; Request::probing() returns the posted value for that field only, validateForm answers just that field's result without side effects; client debounced blur/change probe in ZForm.addField. Base for live validation, server-computed fields, dependent sections. (50 LOC; `validation-probe`; estimate: Request.php + validateForm short-circuit (~20 PHP), ZForm client (~30 JS))
- **Background-task status endpoint**: Core route /_zubzet/task/{id} (and per-user list) rendering progress/terminal state with Tasks::forUser ownership (403 otherwise) and a stop-polling signal once terminal; needs core-route registration for framework endpoints and the PR #199 task queue. (55 LOC; `task-status-endpoint`; estimate: core controller + view + route registration)

## Ideas

### 1. Live per-field server validation (validation probe)

Score 7.85 · impact 8.5 · simplicity 7 · wow 8 · 60 LOC from scratch (10 own) · requires: validation-probe

```
View: Z.Forms.create({dom:'profile', formAction:'profile', live:true});
Native: <form method="post" data-z-validate> ... </form>
Controller: unchanged
  $result = $req->validateForm([Field::create('email')->required()->filter(FILTER_VALIDATE_EMAIL)->unique('z_user','email')]);
  if($result->hasErrors) return $res->formErrors($result->errors);
```

**Magic:** An existing form with a `unique` rule shows 'This e-mail is already taken' as soon as the user leaves the field, with no controller change. Every server rule, DB-backed ones included, now runs live, as in Livewire, without a separate validation endpoint.

**Mechanism:** Client (~30 LOC in ZForm.addField): on blur/change, debounced 400 ms, POST getFormData() (file entries stripped) to the URL send() uses, with `Z-Validate: <field>` and CSRF headers. The last request wins (the previous jqXHR is aborted). The result calls markValid/markInvalid on that field only, with a quiet flag (no scrollIntoView). Server (~15 LOC): at the end of CanValidateForm::validateForm, if request()->header('Z-Validate') is set, respond {result:'probe', field, errors filtered to that field} and ALWAYS exit, answering 'unknown' for fields that are not declared, so a probe can never reach the insert. Built on Request::header() from the island core.

**Risk:** Side effects BEFORE validateForm (moving uploads, mail, logging) run on every probe, so it is opt-in per form. In multi-validateForm flows only the first call answers. Blur-speed `unique` probing allows enumeration, so recommend throttling.

**Simplicity rationale:** This is essentially the validation-probe block (Request header, a validateForm short-circuit, a debounced ZForm probe), which needs no island code, but the guarantee that a probe never reaches the insert is security-sensitive.

**Judges:**
- Huge for every form with unique rules and needs no controller change. The catch is that side effects before validateForm run on every probe, so it has to be opt-in and documented. The always-exit guard is the critical security line.
- High value, but the cost is hidden: any side effects that run before validateForm (uploads, mail, logging, audit rows) run on every blur, multi-validateForm flows behave inconsistently, and the forced exit is new control flow in a core trait. The probe must keep the CSRF and permission checks (never skip checkPermission because Z-Validate is set). unique/exist turns into a fast enumeration oracle, so ship a throttle and don't just recommend one. Keep it opt-in.

### 2. Server-computed fields (fill from controller)

Score 7.57 · impact 7.5 · simplicity 7 · wow 8.5 · 75 LOC from scratch (25 own) · requires: validation-probe

```
View: Z.Forms.create({dom:'address', formAction:'address', live:true});
Controller (one line, before validateForm):
  if($zip = $req->probing('zip')) return $res->fill(['city' => Geo::cityFor($zip), 'state' => Geo::stateFor($zip)]);
```

**Magic:** The user types a postcode and tabs out, and city and state fill in from server logic (VAT-ID lookup, IBAN to bank name, SKU to price). It takes one PHP line and no JS, in a plain server-rendered form.

**Mechanism:** Reuses the Z-Validate probe. Request::probing($name) returns the posted value when header('Z-Validate') === $name, otherwise null. Response::fill(array) emits {result:'fill', values} through generateRest. The client sets .value (never innerHTML) only on same-named fields of the same ZForm that the user has not touched since the probe started, then dispatches `input`. ~10 LOC PHP, ~15 LOC JS.

**Risk:** It must never overwrite a field being edited (per-field dirty timestamp). Fill is limited to the same form, so the server cannot touch arbitrary DOM. Slow external lookups need a timeout.

**Simplicity rationale:** It only needs the small validation-probe header plus Response::fill and a client value setter, but controller code that runs before validateForm and the touched-field race add some risk.

**Judges:**
- Builds almost free on top of the probe. Postcode, VAT ID and SKU lookups are everyday needs in business apps. Needs the dirty-field guard.
- Small once the probe exists, but it inherits every probe caveat. The probing() line sits before validateForm, so it runs without validation. The value must be treated as untrusted, and the controller's permission check must come first. Dirty-timestamp logic and dispatching input (which can loop into another probe) are the edge cases. Outbound lookups need timeouts, or blur turns into a DoS and SSRF amplifier.

### 3. Instant page navigation via Speculation Rules

Score 7.45 · impact 6.5 · simplicity 8.5 · wow 7.5 · 25 LOC from scratch (25 own)

```
{{-- once in the layout, after <x-zubzet::head> --}}
<x-zubzet::instant eagerness="moderate" except="/admin/*"/>
<a href="/login/logout" data-z-no-prefetch>Logout</a>
// controller: if($req->isPrefetch()) skip view counters
```

**Magic:** Internal links in a classic multi-page PHP app load instantly because the browser prefetches or prerenders on hover. There is zero framework JS and no router, and Back/forward stays real navigation. Unsupported browsers just ignore it.

**Mechanism:** The component prints one <script type="speculationrules"> JSON block (json_encode + JSON_HEX_TAG): prefetch by default (prerender opt-in), href_matches rootFolder+'*', excluding [data-z-no-prefetch], links inside islands, /login/logout and `except` patterns, eagerness moderate. A per-link opt-in mode (only a[data-z-prefetch]) is available for cautious apps. Request::isPrefetch() reads Sec-Purpose. Polling islands wait for document.prerendering to end (2 lines). ~25 LOC.

**Risk:** State-changing GET routes in consumer apps would fire on hover, so it is opt-in, prefetch-only by default, excludes logout and is documented loudly. A strict CSP needs a nonce or inline-speculation-rules. It adds server load.

**Simplicity rationale:** It is one self-contained Blade component that emits a speculationrules JSON block plus Request::isPrefetch, with nothing needed from the catalog; one point off because prefetching GET links that have side effects (logout, counters) is a state risk.

**Judges:**
- One tag speeds up the whole MPA. The risk from GETs that change state in consumer apps keeps it opt-in. Excellent value for the size.
- Tiny code, browser-native. The big hazard is consumer apps with state-changing GET links (archive?id=, delete links, logout variants), which would fire on hover. Default to the per-link opt-in mode, not href_matches '*'. CSP needs a nonce.

### 4. Flash that survives redirects and reloads (with focus target)

Score 7.38 · impact 8 · simplicity 8 · wow 5.5 · 60 LOC from scratch (60 own)

```
// works for ZForm success (location.reload), rerouteUrl and island responses alike
$res->flash('success', 'Order saved', focus: 'order-'.$id);
$res->rerouteUrl('/orders');
{{-- target page: nothing to add --}}
<tr id="order-{{ $o->id }}">...</tr>
```

**Magic:** The toast appears on the NEXT page, after a full redirect or ZForm reload, and the page scrolls to and pulses the row that was just saved. 'Saved! ...where did my item go?' disappears with one line, no session and no layout edit.

**Mechanism:** Response::flash() sets a short-lived cookie z_flash (JSON {t,m,f}, SameSite=Lax, path=rootFolder, Max-Age=30, ~1 KB cap), or sends Z-Island-Flash for island requests (same renderer). Z.js reads and deletes the cookie on DOMContentLoaded, renders the toast with textContent in a runtime-created container, and if f matches ^[A-Za-z0-9_-]{1,64}$, scrolls getElementById(f) into view and adds .z-pulse (honours reduced motion). ~15 LOC PHP, ~40 LOC JS, ~6 lines CSS.

**Risk:** The cookie is client-writable, so only text is ever rendered (textContent) and the type is an enum. The cookie is cleared before rendering to avoid loops. With multiple tabs, the first one to load shows the flash.

**Simplicity rationale:** The cookie flash works on its own from plain develop: a Response::flash method, a Z.js reader and a toast renderer, plus a little CSS. The island header path is optional, and the only risk is cookie scope and escaping.

**Judges:**
- Solves the 'where did my item go' problem on every ZForm reload. Rendering with textContent only keeps the client-writable cookie safe.
- Solves a universal pain. The cookie is client-writable, so use textContent only, an enum type and an id regex (already planned). Cookie path and subfolder installs, plus the multi-tab race, are minor. Clear the cookie before rendering.

### 5. Server autosave (no Save button)

Score 7.38 · impact 6.5 · simplicity 9.0 · wow 6.5 · 30 LOC from scratch (30 own)

```
Z.Forms.create({dom:'prefs', formAction:'prefs', autosave:'1.5s'});
Controller: unchanged update path ($req->validateForm(...); update; return $res->success();)
```

**Magic:** Settings pages behave like Notion or macOS preferences: toggle or stop typing and a quiet 'Saved' appears. Invalid fields are flagged inline and not saved, using the same controller the Save button used.

**Mechanism:** ZForm option: on input/change, debounce (clamped to at least 800 ms) and call send() with a quiet flag. Success shows a 'Saved' hint without a reload; formErrors marks fields without scrolling. The existing isSending + 300 ms guard serialises calls, and a trailing flag re-sends once. It composes with the live probe and the stale-edit guard. ~30 LOC.

**Risk:** Safe for update controllers only. On insert forms every autosave creates a row, so refuse unless the form has an id field or uses firstSubmit(). CED fields force doReload and are excluded.

**Simplicity rationale:** A debounced ZForm option reusing the existing send()/isSending guard in develop; no server change and no blocks needed.

**Judges:**
- Settings pages become pleasant with an unchanged controller. Refusing insert forms is the key guard.
- Debounced send() is easy. The insert-vs-update ambiguity, reload-on-success semantics and CED exclusions are not. Each autosave is a full write with audit and side effects, which multiplies server load. Must refuse insert forms reliably, not heuristically.

### 6. Upload progress, cancel and ETA for ZForm

Score 7.2 · impact 7 · simplicity 9.0 · wow 5 · 30 LOC from scratch (30 own)

```
Automatic for any ZForm whose FormData contains a non-empty File. Optional: Z.Forms.create({..., progress:'#my-progress'}) or listen to 'z:upload:progress'.
```

**Magic:** Attaching a 200 MB video to an existing form shows a live Bootstrap progress bar reading '63% · 12 s left', plus a Cancel button. The app adds no code.

**Mechanism:** In ZForm.send, only when a File with size>0 is present, add a jQuery `xhr` factory with an upload progress listener. A lazily created .progress element (role=progressbar, aria-valuenow, textContent only) shows the progress, with the ETA from a moving average and a bubbling z:upload:progress event. Cancel calls jqXHR.abort(). After 100% it shows 'Processing...'. ~30 LOC.

**Risk:** Forms without files must stay byte-identical (no bar). It measures upload only, not server processing.

**Simplicity rationale:** This is a self-contained Z.js tweak in the existing ZForm.send (a jQuery xhr factory with an upload progress listener) and needs nothing from the island stack.

**Judges:**
- Trivial jQuery xhr hook, zero app code, and an obvious UX gap fixed.
- A well-trodden jQuery xhr factory pattern, isolated to file forms. Low risk: textContent only. A clear quality-of-life win.

### 7. List state: sort links, pager and listState() helper

Score 7.1 · impact 8.5 · simplicity 7 · wow 5 · 110 LOC from scratch (110 own)

```
// controller
$s = $req->listState(per: 25, sort: ['name', 'created_at', 'total'], default: '-created_at', filters: ['q', 'role']);
$rows = $model->search($s->filters, $s->sortCol, $s->sortDir, $s->limit, $s->offset);
{{-- view --}}
<th><x-zubzet::sort-link field="total">Total</x-zubzet::sort-link></th>
<x-zubzet::pager :state="$s" :total="$total"/>
```

**Magic:** One controller line gives validated page, per-page, sort and filter values plus offset/limit. Click a header to sort, click again to reverse, with aria-sort set. One tag renders pagination that keeps filters. It works as plain GETs without JS, swaps in place inside nav islands and lands in the URL via mirror: a server-side data grid with no app JS.

**Mechanism:** ListState (~50 LOC) reads page, per (clamped 1..200), sort (whitelist only, so a raw column never reaches SQL; it falls back to the default) and named scalar filters (max 200 chars), and exposes ->url($override). Request::sort() is usable standalone (~15 LOC). The sort-link component toggles the direction and drops `page` (http_build_query). The pager is windowed (1 … 4 5 [6] 7 8 … 40) with rel=prev/next and aria-current. ~45 LOC Blade.

**Risk:** The whitelist is the security boundary; docs must never show raw getGet('sort') in SQL. Large OFFSETs are slow, so document keyset pagination. Coupled to Bootstrap markup, which head already ships.

**Simplicity rationale:** Self-contained PHP ListState class, Request::sort and two Blade components on top of develop; the sort whitelist is a security-sensitive detail but it is plain server code.

**Judges:**
- Every admin panel rewrites this, and the whitelisted sort closes an SQL-injection footgun. It is mostly ordinary PHP/Blade, and well above 100 LOC.
- Very high everyday value. The sort whitelist is a real security improvement over ad-hoc getGet('sort') SQL. More PHP surface than ~150 LOC suggests, and it couples to Bootstrap markup. Clamp page as well, to avoid huge OFFSETs.

### 8. Auto busy state for buttons (no double submit)

Score 7.03 · impact 7.5 · simplicity 9.0 · wow 3.5 · 30 LOC from scratch (30 own)

```
{{-- nothing: every ZForm submit button and Z.Request trigger gets it --}}
<button class="btn btn-primary" data-z-busy="Saving...">Save</button>
```

**Magic:** Clicking Save instantly disables the button, shows a spinner and 'Saving...' and sets aria-busy, then restores it when the response arrives. Double submits on slow connections vanish across the whole app without touching a view.

**Mechanism:** Wrap the $.ajax in ZForm.send with busy(submitButton) and .always(unbusy), and hook z:island:before/loaded for other triggers. busy() stores innerHTML in a WeakMap, sets disabled/aria-busy and inserts a spinner + textContent label, showing the spinner only after 150 ms to avoid flicker. ~30 LOC.

**Risk:** Must not re-enable a button the app disabled itself (use a data flag we set). Restores on network errors too.

**Simplicity rationale:** Pure Z.js wrap around the existing ZForm.send $.ajax plus a WeakMap busy/unbusy helper; the z:island hooks are optional extras.

**Judges:**
- App-wide fix with zero view changes. Low risk.
- Low-risk, broad win. Restoring innerHTML from a WeakMap can clobber DOM that changed mid-request. Use a data flag so app-disabled buttons are not re-enabled. A busy button inside a swapped island is moot.

### 9. Self island: wrap a block of an existing page to make it live

Score 6.93 · impact 8.5 · simplicity 4 · wow 8.5 · 478 LOC from scratch (20 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
{{-- dashboard.blade.php, rendered by the normal action --}}
<x-zubzet::island self="kpis" every="10s">
  @foreach($kpis as $k)
    <div class="kpi" data-test="kpi-{{ $k['key'] }}">{{ $k['value'] }}</div>
  @endforeach
</x-zubzet::island>
```

**Magic:** Wrap any block of an existing page and it becomes live. There is no route, no action, no @fragment and no spinner: the first paint is server-rendered inline, then the island polls its own page and swaps only that block.

**Mechanism:** The `self` prop sets src = the current GET path+query and fragment = the name, skips the self-recursion guard, and registers the slot as a fragment ($__env->fragments[$self] = (string)$slot) so Island::respond finds it in island mode. The component emits data-z-loaded="1", so the runtime skips the first fetch and only schedules (it can emit data-z-etag so the first poll can already 304). ~15 LOC PHP, ~5 LOC JS.

**Risk:** The whole controller runs per poll (pair it with notModified). Duplicate self names collide, so describe() rejects them per render. On POST-rendered pages it falls back to static output.

**Simplicity rationale:** The idea itself is tiny, but from develop it needs the whole fragment contract, the island client runtime with the script scope runner, and the island component, about 460 LOC before it adds anything.

**Judges:**
- Possibly the best ratio: wrap a block and it is live, with no route. Pair it with notModified for cost.
- Largest wow per line: zero routes. Registering the slot as a fragment relies on Blade internals ($__env->fragments). The full controller runs per poll, so pair it with notModified. Non-deterministic content defeats the ETags. GET-only fallback is correct.

### 10. Declarative confirm on anything (incl. type-to-confirm)

Score 6.92 · impact 7.5 · simplicity 8 · wow 4.5 · 55 LOC from scratch (55 own)

```
<a href="/project/archive?id={{ $p->id }}" data-z-confirm="Archive {{ $p->name }}?">Archive</a>
<button type="submit" data-z-confirm="Delete forever?" data-z-confirm-type="{{ $p->name }}" data-z-confirm-ok="Delete" class="btn btn-danger">Delete</button>
```

**Magic:** Adding one attribute gives any link, submit button, ZForm submit or form a styled, accessible <dialog> confirm. GitHub-style 'type the name to confirm' works without writing JS.

**Mechanism:** One capture-phase click/submit listener on window checks closest('[data-z-confirm]'), calls preventDefault + stopImmediatePropagation and opens a native <dialog> via showModal (focus trap, Esc and inert background for free). The message is set with textContent, and the optional input must equal data-z-confirm-type before OK is enabled. OK sets a one-shot WeakSet pass flag and re-dispatches el.click() / form.requestSubmit(submitter). Falls back to window.confirm. ~55 LOC JS, 0 PHP.

**Risk:** It must run before ZForm's own click handler (window capture). Type-to-confirm is UX only; the server must still authorize.

**Simplicity rationale:** Self-contained Z.js capture listener plus native <dialog>, no PHP and no blocks; only risk is the one-shot re-dispatch of click/requestSubmit.

**Judges:**
- Replaces many ad-hoc confirm() calls. Re-dispatching around ZForm handlers needs testing.
- Re-dispatching events after preventDefault (WeakSet pass flag, requestSubmit with submitter, capture ordering relative to ZForm and jQuery handlers) is notoriously quirky. Message via textContent. Server must authorize regardless.

### 11. Local draft autosave and restore (survives crashes and session expiry)

Score 6.88 · impact 7 · simplicity 7 · wow 6.5 · 70 LOC from scratch (70 own)

```
Z.Forms.create({dom:'post', formAction:'post', draft:true});   // or draft:'session'
Native: <form method="post" data-z-draft="post-{{ $post->id }}"> ... </form>
```

**Magic:** After a browser crash, a dead laptop, a Back press or an expired session, the form offers 'Restore your unsaved draft from 14:32? [Restore] [Discard]' and the long text comes back. No server tables are needed.

**Mechanism:** Key: 'z:draft:' + Z.Request.userKey (a short HMAC of the user id emitted by head, 'anon' for guests) + rootPath + pathname + form id. getValues() is saved, debounced 1 s, together with a hash of the server-rendered initial values, so the banner can warn 'the record changed since'. The draft is flushed right before a 401 reload and cleared on success / z:saved. Values are restored only into fields that still hold their initial server value, and never auto-submitted. Excluded: password, file, hidden, _csrf/_zver, autocomplete=off, data-z-nodraft. The cookie watcher deletes z:draft:* before the logout reload. ~60 LOC, everything in try/catch.

**Risk:** Shared computers: localStorage persists until logout, so offer draft:'session' (sessionStorage) and recommend that sensitive forms opt out. Large textareas are capped (~200 KB) to stay under quota.

**Simplicity rationale:** Needs no catalog block (ZForm getValues exists), but ~60 LOC JS plus a head-emitted user key, with storage, per-user privacy, stale-record and logout-clearing edge cases.

**Judges:**
- Valuable for long text, but there are many edge cases: keying, shared computers, stale-record hashing, exclusions. Ship the session variant first.
- Lots of edge cases: key scoping, restore-into-untouched-only, quota, exclusions, logout purge, record-changed hash. Form PII lands in localStorage on shared machines, and the XSS blast radius grows. Default to sessionStorage, or require an explicit opt-in per form. The userKey HMAC must not be derivable from a guessable id.

### 12. Unsaved-changes guard (with cross-tab notice)

Score 6.8 · impact 7.5 · simplicity 8 · wow 4 · 35 LOC from scratch (35 own)

```
Z.Forms.create({dom:'order', formAction:'order', guard:true});
Native: <form method="post" data-z-guard> ... </form>
```

**Magic:** Closing the tab, clicking a nav link, using Back or an island redirect while a form has unsaved edits brings up the browser's 'Leave site?' prompt. A successful save disarms it silently. A second tab with the same form shows 'You have unsaved changes to this form in another tab'.

**Mechanism:** ZForm already flips to its 'unsaved' hint in onFieldChange: set this.dirty there and clear it on success or reset BEFORE location.reload. A module-level Set of dirty guarded forms feeds one beforeunload listener. Native [data-z-guard] forms use a delegated input listener and clear on submit. Island swaps already defer on dirty forms; the Z-Island-Redirect handler checks the same Set and asks with confirm(). An island blocked by a dirty form shows the 'new content' pill instead of waiting forever. Optional BroadcastChannel('z-forms') provides the cross-tab hint. ~30 LOC.

**Risk:** Opt-in for plain forms, so existing apps see no new prompts. Must clear before ZForm's own reload and island redirects to avoid spurious prompts, and must not block the logout-cookie reload. Browsers ignore custom message text.

**Simplicity rationale:** develop's ZForm already detects unsaved state in onFieldChange, so a dirty Set plus one beforeunload listener is small; the island-defer and redirect hooks are optional, and beforeunload quirks cost a point.

**Judges:**
- Small and robust, and it prevents real data loss. Getting the clear-before-reload ordering right is the only subtle part. The cross-tab notice is optional fluff.
- A solid, well-understood pattern. The real work is clearing it before ZForm's own reload, logout and island redirects, otherwise users get spurious prompts. Drop the BroadcastChannel cross-tab part: it adds complexity for little value.

### 13. Export-the-current-view link

Score 6.72 · impact 7 · simplicity 8 · wow 4.5 · 45 LOC from scratch (45 own)

```
// controller, same action that renders the table
$s = $req->listState(per: 25, sort: [...], filters: ['q', 'role']);
if($req->wants('csv')) return $res->csv('users.csv', $model->search($s->filters, $s->sortCol, $s->sortDir), ['id', 'name', 'email']);
{{-- view --}}
<x-zubzet::export-link format="csv">Download CSV</x-zubzet::export-link>
```

**Magic:** 'Download what I'm looking at': the export always matches the current filters and sort, even inside an island whose state the host page never sees. There is no second endpoint and no duplicated query logic.

**Mechanism:** export-link renders <a href=path?query&format=csv download>. Inside islands a 6-line hook rewrites the href from the island's current src on z:island:loaded. Request::wants() checks an enum-validated format. Response::csv() (~25 LOC) streams fputcsv with Content-Disposition and prefixes cells starting with = + - @ with ' (formula-injection guard).

**Risk:** Exports bypass `per`, so large tables need a row cap or an unbuffered cursor. Same route means same permissions.

**Simplicity rationale:** Response::csv, Request::wants and a small Blade link are standalone in develop (the island href hook is optional); formula-injection guard is the main edge case.

**Judges:**
- CSV export is requested in every business app. The formula-injection guard is a nice bonus. Depends on listState.
- Useful. The formula-injection guard is good, but also handle cells that start with tab or CR. Unbounded exports need a cap and streaming. Same route means same permissions.

### 14. Accessible form errors: focus, scroll and announce

Score 6.7 · impact 7 · simplicity 9.0 · wow 3 · 25 LOC from scratch (25 own)

```
{{-- nothing; every ZForm gets it --}}
$res->formErrors($v->errors);   // unchanged server code
```

**Magic:** After a failed save the page scrolls to the first invalid field and focuses it, and screen readers hear '2 fields need attention: Email, Password'. Long admin forms stop failing silently below the fold.

**Mechanism:** In ZForm.send's formErrors branch, after markInvalid: find the first .is-invalid, scrollIntoView (smooth unless reduced motion), focus({preventScroll:true}), and write a summary of label texts (via a Z.Lang key) into a single visually-hidden aria-live=assertive region. It runs before the app's formErrorHook so the hook wins. ~25 LOC.

**Risk:** Label lookup falls back to the field name. It must not fight app hooks that move focus.

**Simplicity rationale:** A few lines in develop's existing ZForm.send formErrors branch (markInvalid already exists) plus an aria-live region and one Z.Lang key.

**Judges:**
- Quiet but meaningful, with an accessibility gain on every form. Should simply be in core.
- Small, clearly correct, and improves accessibility everywhere. Must coexist with app formErrorHooks. Strongly recommended as a default.

### 15. Lost-update guard (optimistic concurrency) with live 'someone changed this' banner

Score 6.47 · impact 8 · simplicity 4 · wow 7.5 · 559 LOC from scratch (55 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, validation-probe

```
View (inside the form): <x-zubzet::edit-version :of="$order" :only="['status','note']" watch="20s"/>
Controller save path: $req->assertFresh($order, by: $order['updated_by_name']);   // before validateForm/update
```

**Magic:** When two people edit the same order, the second save no longer silently overwrites the first. It gets 'Anna changed this order since you opened it. [Reload] [Overwrite]' and the form stays filled. While still typing, a banner slides in: 'This order was changed 1 min ago.' One tag and one line give Google Docs-style conflict awareness in a CRUD app.

**Mechanism:** The component emits a hidden _zver = HMAC(json of the selected columns, or an explicit version/updated_at). assertFresh($row) compares it with the current row and on mismatch (unless _zoverwrite=1) sends 409 + formErrors [{name:'_zver', type:'stale'}] + Z-Island-Flash, then exits. A missing _zver passes, so it is drop-in. ZForm treats 409 as non-success without reloading and keeps the input. Inside islands, ZForm can additionally send If-Match with the island's version ETag (from notModified) instead of a hidden field. With watch, the client sends the Z-Validate probe for `_zver` every 20 s while visible; assertFresh answers it before anything else. ~30 LOC PHP, ~25 LOC JS.

**Risk:** The fingerprint must exclude volatile columns (use :only or an integer version column). It is check-then-write without a lock, so strict guarantees need the check inside the update transaction. Watch polling costs one controller run per 20 s per open editor. Must run after CSRF verification so a 409 never masks a 403.

**Simplicity rationale:** The HMAC hidden field plus assertFresh and ZForm 409 handling is small, but the flash banner pulls in response-effects (and with it the island stack) and the watch mode needs the estimated validation probe; the HMAC/409 path is also security-sensitive.

**Judges:**
- Silent overwrites are a real business bug. The core (_zver plus assertFresh) is simple. Watch polling and If-Match add complexity, so defer them.
- Valuable for CRUD apps, but the volatile-column fingerprinting, the TOCTOU without a lock, 409 handling in ZForm, and watch polling (a full controller run every 20 s per editor) add up. It must run after CSRF and permission checks. The HMAC key must be server-secret. Exposing the editor's name (by:) leaks across tenants unless scoped.

### 16. Ticking relative times

Score 6.45 · impact 6 · simplicity 8 · wow 5 · 43 LOC from scratch (43 own)

```
Updated <x-zubzet::time :at="$order->updated_at"/>
{{-- renders <time datetime="2026-10-23T09:14:00Z" title="23.10.2026 11:14" data-z-time>23.10.2026 11:14</time> --}}
<x-zubzet::time :at="$deadline" format="relative" until/>
```

**Magic:** '3 minutes ago' stays true while the tab is open, ticking to '4 minutes ago' in the user's language without polling the server, and it is still a correct absolute date with JS off or in e-mails. It also keeps ETags stable: island HTML holds a fixed ISO timestamp instead of 'x seconds ago', so polls keep returning 304.

**Mechanism:** An anonymous zubzet component (~8 lines) normalises DateTimeInterface|int|string to UTC ISO plus a server-formatted fallback. Z.js runs one shared 15 s timer (paused while hidden, also run on z:island:loaded) using Intl.RelativeTimeFormat(lang, {numeric:'auto'}). ~35 LOC JS.

**Risk:** Layouts hard-code lang="en", so read <time lang> or html lang and fall back to navigator.language. Client clock skew can optionally be corrected with a server 'now' emitted in head.

**Simplicity rationale:** One small anonymous Blade component plus a shared Intl.RelativeTimeFormat timer in Z.js; no island machinery needed (z:island:loaded rerun is optional).

**Judges:**
- Small, and it also stabilizes ETags, which matters for the 304 strategy.
- Tiny and robust. Bonus: it stabilizes island ETags. Locale fallback because of the lang="en" hard-coding. Clock skew is minor.

### 17. Rules written once: client constraints generated from PHP Field rules

Score 6.45 · impact 6 · simplicity 8 · wow 5 · 65 LOC from scratch (65 own)

```
Controller: return $res->render('profile', ['rules' => ProfileForm::fields()]);
View: <x-zubzet::form-rules for="profile" :fields="$rules"/>
      <script>Z.Forms.create({dom:'profile', formAction:'profile'});</script>
Save path: $req->validateForm(ProfileForm::fields());
```

**Magic:** The PHP rule list the server enforces also drives instant client-side feedback (required, length, e-mail/int/url, allowed values) with the exact Z.Lang messages the server returns. The rules cannot drift because they are defined once.

**Mechanism:** Field::toClientRules() maps a safe subset (required, length, filter EMAIL/INT/URL, in). unique/exist stay server-only and are left to the live probe. The component emits inert `<script type="application/json" data-z-rules="profile">` (JSON_HEX_TAG). ZForm reads it on construct and checks on input, producing the same error objects. ~25 LOC PHP, ~35 LOC JS.

**Risk:** UX only; the server stays authoritative (document it). Closure rules cannot be mirrored and are skipped. JSON must use JSON_HEX_TAG.

**Simplicity rationale:** Independent of islands: a Field mapper, one Blade component and ZForm reading inline JSON, all on top of what develop already has; the main risk is client and server semantics drifting apart.

**Judges:**
- Mostly redundant once the live probe exists, and it adds a second validator that can drift on message formatting. Nice, but not essential.
- Keeping two validators in sync (PHP filter_var vs JS regex for EMAIL/URL) is a parity bug farm, and messages and length semantics (bytes vs chars) drift. It is UX only, so the security risk is low (JSON_HEX_TAG is required). If the live probe ships, it covers most of the value.

### 18. Fluent response effects: refresh / toast / closeModal / resetForm

Score 6.43 · impact 8.5 · simplicity 4 · wow 6.5 · 514 LOC from scratch (60 own) · requires: fragment-contract, script-scope, island-runtime, response-effects

```
// controller, after a ZForm save (works on success() JSON too, not only island HTML)
$res->refresh("cart", "orders")->toast("Order saved")->closeModal()->resetForm();
return $res->success();
```

**Magic:** A modal edit form saves, the modal closes, the cart badge and order table elsewhere re-render and a toast appears, all from one chained PHP line. There is no JS in the view, no event wiring and no page reload.

**Mechanism:** A thin fluent layer over Island::header / islandTrigger / islandFlash. Effects accumulate in a static list re-sent as one replaced header Z-Island-Effects: [{do:'refresh',on:[...]},{do:'toast',...},{do:'close'},{do:'reset'}] (json_encode, header(...,true)). One client dispatcher runs from both the island pipeline and ZForm.send success. close/reset act only on the initiating element's nearest <dialog>/.modal/form, and the server never supplies selectors. Verbs are a closed enum. ~25 LOC PHP, ~35 LOC JS.

**Risk:** The client must track the initiating element. Toast text is truncated (~1 KB header). islandTrigger/islandFlash stay as the underlying API for BC.

**Simplicity rationale:** The fluent PHP and the client dispatcher are small, but the refresh verb needs the island runtime and the trigger pipeline, so from scratch it pulls in the full island stack.

**Judges:**
- Covers the most common modal-save flow with one line. Tracking the initiating element on the client is the tricky part.
- High-value ergonomics. Tracking the initiating element across ZForm and islands is the tricky part. The closed verb enum and the rule that the server never supplies selectors are the right security posture. Header size limits apply to toasts.

### 19. Dependent form sections that keep what you typed

Score 6.4 · impact 7.5 · simplicity 4 · wow 8 · 461 LOC from scratch (48 own) · requires: fragment-contract, script-scope, island-runtime

```
<form id="order" method="post">
  <select name="country">...</select>
  @fragment('shipping')
  <fieldset data-z-depends-on="country delivery" data-z-fragment="shipping">
    @if(request()->formValue('country', $order->country) === 'DE') <input name="zip" ...> @endif
  </fieldset>
  @endfragment
</form>
```

**Magic:** Changing the country re-renders only the shipping block from the same Blade view, using server conditionals for German postcode, US state or customs fields. The street already typed in that block survives the swap. There is no extra route, no JS and no duplicated template.

**Mechanism:** Client (~40 LOC): on change of the named fields, GET the current path with only those fields as query params plus Z-Island and Z-Island-Fragment headers, reusing the island fetch pipeline (last request wins). Before replaceChildren it copies values/checked by name from the old fieldset into the new one and restores focus by name. Server: Request::formValue($name, $default) returns the GET value in island mode (~8 LOC).

**Risk:** Only the declared depends-on fields go into the URL (it ends up in access logs), and password/file fields are refused. The action must be a side-effect-free GET. @fragment inside a loop keeps only the last iteration.

**Simplicity rationale:** Needs the server fragment contract and the island fetch/swap pipeline from scratch; the extra value-copy/focus-restore logic is small but stateful.

**Judges:**
- Country-dependent fields are a classic pain point, and this needs no JS or extra route. Value carry-over and focus restore are the fiddly parts.
- Copying values across the swap, focus restore, the @fragment-in-loop caveat and requiring the page action to be a side-effect-free GET are all subtle. Form values in query strings end up in logs and Referer headers. Personal data like addresses is a privacy concern, beyond password and file fields.

### 20. Idempotent submit (no duplicate orders)

Score 6.38 · impact 7 · simplicity 7 · wow 4.5 · 45 LOC from scratch (45 own)

```
Native: <form method="post" data-z-once> ... </form>   (ZForm: automatic)
Controller: if(!$req->firstSubmit()) return $res->success();   // or ->rerouteUrl('/orders')
```

**Magic:** Double-clicks, a held Enter key, network retries, Back+resubmit, offline replay and the session-rescue retry all create the order once. The duplicate gets the same success response.

**Mechanism:** Client: a hidden _zonce UUID (crypto.randomUUID), created when the form first becomes dirty and regenerated after success, so retries of the same intent share the key (it is also sent as a Z-Idempotency-Key header). Native forms disable the submitter and re-enable it on pageshow. Server: Request::firstSubmit() does INSERT IGNORE into z_submit_once(token,user_id,created) and returns affectedRows===1, with a 24 h TTL purge on 1% of calls. The unique key keeps it correct on Galera. ~15 LOC JS, ~20 LOC PHP plus a core migration.

**Risk:** Needs a core table; firstSubmit() returns true (logged once) when it is missing so it stays drop-in. It is not a security control (clients can mint keys) and only dedupes honest retries. Call it only after validation passes, otherwise fixing a typo looks like a replay.

**Simplicity rationale:** Small and independent of islands, but it adds a core migration, server-side state with a TTL purge and Request changes, and touches Z.js, Request.php and the migrations.

**Judges:**
- A solid foundation for replay and rescue. A core migration plus developer discipline (call only after validation) reduces its drop-in quality.
- Needs a core migration and table, a purge job, and correct call placement (after validation). Failing open when the table is missing silently disables it. Not a security control. Client double-click disabling covers most of the practical value more cheaply.

### 21. Session-expiry rescue: log in in a dialog, then the save completes

Score 6.3 · impact 7.5 · simplicity 3 · wow 9 · 479 LOC from scratch (66 own) · requires: fragment-contract, script-scope, island-runtime

```
Automatic for every ZForm (opt out: Z.Forms.create({..., rescue:false})). No controller change.
```

**Magic:** After 40 minutes of writing the session has expired. Clicking Save opens a login dialog over the form instead of losing the text. After login the dialog closes and the original save goes through, without leaving the page, which is normally only possible in SPA frameworks.

**Mechanism:** Server (~6 LOC): extend the island 401 branch in Request::checkPermission to XHR form posts (isPost && hasFormData && X-Requested-With), returning 401 + Z-Island-Auth: login instead of a 200 login page. Client (~60 LOC): on 401 ZForm keeps its FormData (files included) in memory and opens a <dialog> containing an island for /login. Login success arrives as 204 Z-Island-Redirect, which the dialog intercepts. If the userKey (re-read from a response header) matches the original user, it retries send() once with fresh CSRF headers; otherwise it discards the payload and reloads. The cookie watcher dispatches a cancelable z:session:expired, and the rescue cancels it.

**Risk:** The payload must never be replayed as a different user (userKey check plus server permission re-check). 2FA/SSO flows that leave the page fall back to the draft stash plus a reload. One retry only. The CSRF token is read at retry time because it may rotate on login.

**Simplicity rationale:** It needs the island 401/204-redirect contract and a client island for /login inside the dialog; on top of that are ~66 LOC of auth-sensitive retry logic (FormData kept in memory, CSRF refresh, user-identity check), which is a lot of edge-case risk.

**Judges:**
- Real pain in internal tools, but the login flow, userKey match, CSRF rotation and 2FA fallbacks make it closer to M than S. High value if it is done carefully.
- Impressive, but fragile: login inside an island dialog, redirect interception, CSRF rotation, the user-identity check, file payload retention, and 2FA/SSO fallbacks. It changes the 401 behaviour of every XHR form post (a drop-in risk for apps that parse the login HTML). Replaying as a different user is the critical bug class. Defer until the core is stable.

### 22. Global top progress bar for slow requests

Score 6.22 · impact 5.5 · simplicity 9 · wow 3.5 · 35 LOC from scratch (35 own)

```
{{-- nothing; appears for any island fetch, ZForm save, Z.Request call or redirect --}}
<style>:root{ --z-progress-color: var(--primary); }</style>
```

**Magic:** Any request slower than 300 ms shows a thin GitHub/YouTube-style bar at the top of the page, and fast ones show nothing. The whole app feels responsive without a loader in any view.

**Mechanism:** An in-flight counter fed by $(document).ajaxSend/ajaxComplete (Z.Request and ZForm) and z:island:before/loaded/error. Background polls are excluded via an event detail flag. On 0->1 a 300 ms timer shows a fixed 2px div animating toward 90%; on ->0 it jumps to 100% and fades. It also covers the gap before a ZForm reload. pointer-events:none. ~30 LOC JS, ~5 lines CSS.

**Risk:** Polls must be excluded or the bar becomes noise. The colour comes from a custom property so it adapts to app themes.

**Simplicity rationale:** Self-contained Z.js counter on jQuery ajaxSend/ajaxComplete (Z.Request and ZForm already exist in develop) plus a few CSS lines; island events are an optional extra hook.

**Judges:**
- Cheap polish, provided background polls are excluded.
- Simple, and polls must be excluded. Visual polish only. Counter leaks on aborted requests need ajaxComplete coverage.

### 23. @defer: slow section becomes a lazy island, heavy query skipped on first paint

Score 6.2 · impact 7 · simplicity 4 · wow 8 · 433 LOC from scratch (20 own) · requires: fragment-contract, script-scope, island-runtime

```
// controller: pass the expensive part as a closure
view("reports/index", ["summary" => $s, "breakdown" => fn() => model("Report")->heavyBreakdown()]);
{{-- view --}}
@defer('breakdown')
  @fragment('breakdown')
    @include('reports/breakdown-table', ['rows' => $breakdown()])
  @endfragment
@enddefer
```

**Magic:** The page renders instantly with a spinner where the slow table goes, and the table streams in a moment later. The developer wrapped one value in a closure and wrapped the block, with no new route or controller method.

**Mechanism:** A Katana custom if-directive registered in Rendering/Katana/Hooks::register. It returns true only when isIsland() && islandFragment() === $name. Otherwise it echoes a lazy island placeholder (data-z-src = current GET URL, data-z-fragment = name) and returns false, so neither the block nor the closure runs. Reuses Island::respond's fragment branch. ~20 LOC PHP, zero JS.

**Risk:** The inner @fragment is required because an if-directive cannot close a wrapper (document it, or add compileDefer later). GET pages only. Non-closure data is still computed eagerly.

**Simplicity rationale:** The directive is about 20 LOC in Katana Hooks, but the lazy placeholder only works with the fragment contract and the island client runtime (lazy IntersectionObserver, swap, scope run).

**Judges:**
- Small directive with a large perceived-performance win. The closure convention and the required inner @fragment are slightly awkward.
- Clever, but the required inner @fragment and closure convention are awkward ergonomics. A forgotten @fragment just renders empty. The second request re-runs the controller, including non-closure work, and any side effects run twice.

### 24. Native GET filter forms inside islands (live search)

Score 6.15 · impact 7.5 · simplicity 4 · wow 7 · 532 LOC from scratch (25 own) · requires: fragment-contract, script-scope, island-runtime, island-component, nav-islands, swap-hooks

```
<x-zubzet::island src="/users/table" nav url="mirror" key="users">
  {{-- rendered by /users/table itself --}}
  <form method="get" data-z-live="300ms">
    <input name="q" value="{{ $s->filters['q'] ?? '' }}" placeholder="Search">
    <select name="role">...</select>
    <button>Filter</button>
  </form>
  <table>...</table>
</x-zubzet::island>
```

**Magic:** A plain HTML GET form becomes live search: results update as you type, the URL stays shareable, and with JS off it is the same form doing a full-page GET. The server owns the markup and no selectors are wired up.

**Mechanism:** In nav islands, a delegated submit handler catches method=get forms whose action is same-origin and root-prefixed, builds URLSearchParams(FormData) and refreshes the island with that src. data-z-live adds a debounced (min 150 ms) input/change listener and an AbortController (last request wins). Focus and caret are preserved via the focus-preserving swap. ~40 LOC.

**Risk:** GET only (reads); POST forms are ignored. Focus restore depends on stable input names.

**Simplicity rationale:** Submitting GET forms in place is mostly nav-islands already, and the debounce and focus/caret preservation need swap hooks, so it needs the whole island stack plus nav and swap hooks.

**Judges:**
- Live search with plain forms and progressive enhancement. Pairs naturally with listState and focus-preserving swap.
- Debounce, abort and focus preservation are needed, and form action origin and root-prefix validation is mandatory. GET only, so the security risk is low. Depends on the focus-preserving swap.

### 25. Keyed swap with change highlight (swap="keyed", optional full morph)

Score 6.08 · impact 7 · simplicity 4 · wow 7.5 · 529 LOC from scratch (51 own) · requires: fragment-contract, script-scope, island-runtime, island-component, swap-hooks

```
<x-zubzet::island src="/orders/table" every="5s" swap="keyed"/>
{{-- in the view --}}
<tr data-z-key="{{ $o->id }}">...</tr>
CSS (shipped): .z-changed { animation: z-flash 1.2s }
```

**Magic:** A table polls every 5 s, yet checked rows stay checked, an open dropdown stays open and hovered rows do not jump. Only rows that actually changed are replaced and briefly pulse, and new rows fade in, so people notice 'that order just got paid'. It feels like a websocket grid but is a plain foreach.

**Mechanism:** For containers with [data-z-key] children: index old children by key in a Map (never as selectors), replace a child only if its outerHTML differs (and mark it .z-changed, removed on animationend, honouring prefers-reduced-motion), insert new keys in server order and remove missing ones. Unkeyed content falls back to replace. The highlight also works with the plain replace swap (compare textContent hashes by key, capped at 500). ~45 LOC plus 6 lines CSS. A later swap=morph (idiomorph-style, ~80 LOC, matched by id/data-key/tag, data-z-keep to skip) can follow if the 2-level morph proves insufficient.

**Risk:** Inline scripts in changed rows re-run. Server-rendered checkbox state survives only for unchanged rows. Duplicate keys: last wins, with a test-mode warning. Full morph is M and should be deferred.

**Simplicity rationale:** The ~45 LOC keyed diff plus CSS plugs into the island swap through swap-hooks and the swap attribute on the island component, so from develop it needs the full island stack; keyed DOM diffing also has focus and script edge cases.

**Judges:**
- Fixes the main annoyance of polled tables (lost checkboxes and open dropdowns). Keep the morph deferred.
- Two-level keyed reconciliation is manageable, but outerHTML comparison breaks on per-render tokens, scripts re-run in changed rows, and ordering edge cases appear. Keys go through Map lookups and never into selectors (good). Defer full morph.

### 26. Task progress island (self-terminating, for PR #199 background tasks)

Score 6.05 · impact 7.5 · simplicity 3 · wow 8 · 579 LOC from scratch (25 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, task-status-endpoint

```
Controller: $task = Tasks::dispatch(new ExportOrders(['month'=>$m])); return $res->render('orders/export', ['task'=>$task]);
Blade: <x-zubzet::task :task="$task" then="orders.exported">Preparing export...</x-zubzet::task>
Task class: $this->progress(40, 'Writing rows');
```

**Magic:** A real progress bar for a background job in a server-rendered page with zero JS. It fills up with step labels, turns into the result (download link or error) when the worker finishes, stops polling itself and fires a trigger so a results table elsewhere refreshes.

**Mechanism:** Core route /_zubzet/task/{id} renders <progress> plus a label or the terminal state, enforcing Tasks::forUser ownership (403 otherwise). The component wraps <x-zubzet::island every="2s" live>. When terminal, the server sends Z-Island-Every: stop plus Z-Island-Trigger: <then>. Unchanged progress is a 304. ~40 LOC PHP/Blade.

**Risk:** The ownership check is mandatory and ids must not be guessable. Depends on PR #199. Throttle progress() writes to about once per second.

**Simplicity rationale:** It needs the whole island stack plus trigger headers, a new core-route mechanism for framework endpoints, and the PR #199 task queue, which is not in develop yet.

**Judges:**
- A killer demo for background tasks. Depends on PR #199 and the ownership check.
- A compelling use of every + stop + trigger. The ownership check is mandatory (IDOR on sequential task ids), so use unguessable ids. Blocked on PR #199. Throttle progress writes.

### 27. Fragment permalinks, pop-out and print

Score 5.95 · impact 5 · simplicity 7 · wow 6 · 130 LOC from scratch (35 own) · requires: fragment-contract

```
/dashboard?z-only=kpis   (in the address bar)
<x-zubzet::island src="/dashboard" fragment="kpis" every="30s" popout/>
<x-zubzet::print fragment="invoice">Print invoice</x-zubzet::print>
```

**Magic:** Any named part of any page becomes a URL you can paste into chat, and the colleague sees just that widget under their own permissions. One click prints or saves as PDF only the invoice block, with no navbar and no print-CSS gymnastics.

**Mechanism:** In CanRenderView, a GET with ?z-only=<valid name> renders with layout/min_layout (head and Z.js included, so the widget keeps polling) and outputs only that fragment via the same lookup as Island::respond, or 404 if it is missing. Print opens url+'?z-only=invoice&z-print=1', and a static Z.js listener prints after document.fonts.ready. The response is never island-marked. ~20 LOC PHP, ~15 LOC JS.

**Risk:** Normal routing and permissions apply. The namespaced query key is unlikely to clash with app routes.

**Simplicity rationale:** Reuses the fragment-contract @fragment extraction in CanRenderView, plus ~20 PHP and ~15 JS; the popout attribute would additionally need island-component, but it is optional.

**Judges:**
- Printing a single fragment such as an invoice is the practical half. The permalink is a niche use.
- A global query param (z-only) changes rendering for every GET route, a drop-in risk if an app uses that key. Routing and permissions are unchanged. The fragment lookup has to happen outside island mode. Print is a nice niche.

### 28. Too-large upload caught before and after upload

Score 5.92 · impact 6.5 · simplicity 7 · wow 3.5 · 35 LOC from scratch (35 own)

```
Automatic. Optional per field: <input type="file" name="cv" data-z-max="5MB">
```

**Magic:** Today a 50 MB photo makes PHP drop the whole POST and the user gets a meaningless 'error'. With this, picking the file immediately shows 'File too large (max 8 MB)' on that field, and if it slips through, the server returns a proper field error.

**Mechanism:** Head emits Z.Request.maxUpload = min(upload_max_filesize, post_max_size) via a tiny ini parser. ZForm checks file.size on change and calls markInvalid({type:'fileSize'}), a new Z.Lang key. Server: a narrow guard in the ZubZet constructor (XHR POST, empty $_POST/$_FILES, CONTENT_LENGTH > post_max_size) answers formErrors tooLarge. validateForm maps UPLOAD_ERR_INI_SIZE/FORM_SIZE to fileSize. ZForm treats a proxy 413 the same way. ~20 LOC PHP, ~15 LOC JS.

**Risk:** The server guard must stay narrow so normal requests are untouched. The client check is UX only.

**Simplicity rationale:** Little code and no blocks, but it spreads across the head, ZForm, the ZubZet constructor and validateForm, with ini parsing and proxy 413 edge cases.

**Judges:**
- Fixes a confusing failure every PHP app hits. The server guard in the constructor needs care, but it stays small.
- Fixes a real, ugly failure. The constructor guard on CONTENT_LENGTH is in the hot path for every request and must stay narrow. The ini shorthand parser is a minor gotcha. The client check is UX only.

### 29. Server-side undo toast from one controller line

Score 5.88 · impact 6.5 · simplicity 4 · wow 7.5 · 499 LOC from scratch (45 own) · requires: fragment-contract, script-scope, island-runtime, response-effects

```
public function action_delete($req, $res) {
    $this->todo->softDelete($id);
    $res->undo('Deleted "'.$todo->title.'"', '/todo/restore', ['id' => $id], seconds: 8);
    return $res->success();
}
public function action_restore($req, $res) { $this->todo->restore($req->getPost('id')); $res->islandTrigger('todos.changed'); $res->success(); }
```

**Magic:** Every destructive action gets a Gmail-style 'Deleted. Undo' toast with a countdown. Clicking Undo brings the row back in place via refresh-on. No confirm dialog, no client state and no app JS.

**Mechanism:** Extends flash: an undo object {label, post, data} rides in Z-Island-Flash (or the z_flash cookie on reload paths). The post path is validated server-side with the island src rules and again client-side (same-origin, root-prefixed); data is scalars only. The Undo button POSTs through the existing Z.Request path (CSRF headers), then runs the response pipeline, so its triggers refresh the affected islands. The undo route is an ordinary permission-checked write, so no token signing and no undo table are needed. seconds is clamped to 3..30. ~10 LOC PHP, ~35 LOC JS.

**Risk:** Apps must actually soft-delete (document deleted_at + purge). The restore route must re-check ownership, enforce CSRF and be idempotent. Paths only, never full URLs.

**Simplicity rationale:** It extends the flash header and trigger pipeline from response-effects, which depends on the island runtime; its own code is a small Response method plus an undo button, and it needs client-side and server-side path validation.

**Judges:**
- Great UX built on flash, but apps must implement soft delete. Path validation is required.
- Leans on flash plus Z.Request. The POST path must be validated on both sides, and the restore route must re-authorize and enforce CSRF. Apps need soft deletes. The scalar-only data is posted back as-is, so the restore controller must not trust it.

### 30. Keyboard layer: declarative shortcuts, j/k list navigation and '?' cheat sheet

Score 5.88 · impact 4.5 · simplicity 7 · wow 6.5 · 100 LOC from scratch (100 own)

```
<a href="/orders/new" data-z-shortcut="n">New order</a>
<button data-z-shortcut="mod+s">Save</button>
<x-zubzet::island src="/inbox" data-z-shortcut="g i"/>
<table class="table" data-z-keys>
  @foreach($rows as $o)<tr data-z-row data-z-key="{{ $o->id }}"><td><a href="/order/{{ $o->id }}" data-z-primary>#{{ $o->id }}</a></td></tr>@endforeach
</table>
<input type="search" name="q" data-z-key-focus="/">
```

**Magic:** The server decides the shortcuts just by rendering attributes, and they follow permissions automatically because an unrendered button has no shortcut. Tables get Gmail/Linear-style j/k, Enter, x and /, and the focused row survives island refreshes. '?' shows a generated cheat sheet of every shortcut on the page, including ones inside islands loaded later.

**Mechanism:** One document keydown listener (~70 LOC). It ignores editable targets unless mod is held, supports sequences ('g i') with a 1 s buffer, and validates combos against ^(mod\+|shift\+|alt\+)*[a-z0-9?/]( [a-z0-9])?$. It clicks links/buttons, focuses inputs or calls Z.Islands.refresh on islands. Lists use roving tabindex on [data-z-row]; Enter clicks [data-z-primary] (real links), x toggles the row checkbox, and focus is restored on z:island:loaded by data-z-key. The ? overlay is built with textContent from the attribute plus aria-label/text.

**Risk:** Single-key shortcuts conflict with assistive tech (WCAG 2.1.4): list keys are active only when focus is in the list, everything is opt-in per element, and data-z-keys="off" disables globally. Only elements already in the DOM are clicked, so there is no new attack surface.

**Simplicity rationale:** It is a standalone document keydown listener in Z.js with a sequence buffer, roving tabindex and a cheat-sheet overlay. Island refresh and focus restore can be optional hooks, but key-combo and editable-target edge cases plus accessibility keep it from being trivial.

**Judges:**
- Power-user delight, but around 70+ LOC with accessibility pitfalls. Low priority for typical internal-tool users.
- Large, with conflicts against assistive tech and browser and IME shortcuts, plus sequence buffers, roving tabindex and an overlay. WCAG 2.1.4 risk. Niche audience for typical admin apps.

### 31. Hold changes until asked: 'N new items' pill / stale banner (on-change="notify")

Score 5.83 · impact 7 · simplicity 4 · wow 6.5 · 488 LOC from scratch (30 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
<x-zubzet::island src="/tickets/inbox" every="15s" on-change="notify" notify-label="New messages - show"/>
{{-- edit form variant --}}
<x-zubzet::island src="/order/42" fragment="form" every="15s" on-change="notify" notify-label="This record was changed by someone else. Reload">@include('order.form')</x-zubzet::island>
```

**Magic:** Like Twitter or Slack: a polled list never jumps under the reader, and a small pill reads 'New messages - show'. On an edit form, a bar says the record changed elsewhere and never overwrites typed input. The fresh HTML is already downloaded, so clicking applies it instantly.

**Mechanism:** Enum on-change=swap|notify. In notify mode, a 200 with a new ETag is stored as st.pending instead of swapped, and a <button class="z-island-pill" data-test="z-island-pill"> (textContent from the label, max 80 chars, a few inline styles) is inserted at the top. Clicking swaps pending and scrolls into view; later polls overwrite pending. The first load always swaps. For server-rendered first paint, the component emits data-z-etag computed via Island::etag so the first poll can detect changes. ~25 LOC JS, ~5 LOC PHP.

**Risk:** Non-deterministic content (CSRF tokens, 'now') changes the hash on every poll and causes false pills, so watch a deterministic fragment, e.g. a version fragment. A '3 new' count would need a Z-Island-Count header (left out of v1).

**Simplicity rationale:** Storing a pending swap and showing a pill is tiny, but it only exists on top of polling islands, which means the island runtime, the component and the server fragment contract.

**Judges:**
- Small, and avoids content jumping under the reader. False pills from non-deterministic HTML are the main gotcha.
- Simple state plus a button. The real issue is ETag churn from CSRF tokens and timestamps, which causes false pills unless fragments are deterministic. The label goes in textContent, so no risk.

### 32. Named version counters with auto-trigger: versions()->bump()

Score 5.83 · impact 7 · simplicity 4 · wow 6.5 · 569 LOC from scratch (0 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, etag-precheck, version-counters

```
// any write path
model("Orders")->insert($row);
versions()->bump("orders");            // also emits Z-Island-Trigger: orders
// any island action
if($res->notModified(versions("orders", "customers"))) return;
// view
<x-zubzet::island src="/orders/table" every="10s" refresh-on="orders"/>
```

**Magic:** The tab that saved refreshes instantly, every other user's tab picks the change up on its next poll, and every unchanged poll costs a single primary-key SELECT. It feels like push but is plain polling.

**Mechanism:** Core migration z_version(name VARCHAR(64) PK, v BIGINT). bump() does INSERT ... ON DUPLICATE KEY UPDATE v=v+1 and then islandTrigger($name). versions(...) is one SELECT ... IN feeding notModified. Global helper in GlobalReferences.php. ~45 LOC PHP plus migration. Later twist: a single /_zubzet/changes route that batch-polls all HMAC-signed watch keys on a page (watch="order:42") and triggers matching islands, so pages with many islands make one request per tick.

**Risk:** Hot rows on Galera cause certification conflicts (Connection retries; document per-entity names like orders:42). A forgotten bump() leaves data stale; DB-write triggers can automate it. The batched-watch twist is M and should be deferred.

**Simplicity rationale:** It is the version-counters block itself (a migration, a Versions class and a helper), but it feeds the notModified pre-check and triggers refresh-on islands, so from develop it brings the ETag pre-check, effect headers and the full island stack, plus a new core table.

**Judges:**
- Good pairing with notModified, but it needs a migration, developer discipline and Galera hot-row caveats.
- Needs a core migration and a global helper. Galera hot-row certification conflicts are a real operational risk. Forgotten bumps make content stale. Defer the batched watch route.

### 33. Cross-tab trigger relay (BroadcastChannel)

Score 5.8 · impact 6 · simplicity 4 · wow 8 · 489 LOC from scratch (10 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, broadcast-relay

```
Controller (unchanged): $res->islandTrigger('cart.changed');
Blade (in any tab): <x-zubzet::island src="/cart/badge" refresh-on="cart.changed"/>
Opt out: Z.Islands.broadcast = false;
```

**Magic:** Add something to the cart in tab A and the badge in tab B, opened an hour ago, updates instantly, with no websockets, no push server and no polling.

**Mechanism:** Every trigger (Z-Island-Trigger header or Z.Islands.trigger()) is also posted as {t:name, tab} on BroadcastChannel('z-islands:'+rootPath). Receivers run the local trigger without rebroadcasting. Names are validated with the refresh-on regex. Hidden tabs mark islands dirty and refresh on visibilitychange. Triggers are coalesced per frame. ~15 LOC.

**Risk:** Same-origin, same-profile only, so it never crosses users. Refresh storms are avoided by coalescing and skipping islands that are already loading.

**Simplicity rationale:** The relay is about 35 LOC, but a relayed trigger only does something when islands with refresh-on exist, so the whole island stack plus trigger headers has to come along.

**Judges:**
- About 15 LOC for a strong 'how' moment. Coalescing avoids storms.
- Very small and same-origin by construction. Validate names, coalesce, and skip loading islands. Scope the channel by user hash, so a logout or login in another tab doesn't cross-trigger (harmless anyway, since the server re-auths).

### 34. Deep-linkable and remembered island state (url="mirror", persist)

Score 5.75 · impact 6.5 · simplicity 4 · wow 7 · 557 LOC from scratch (70 own) · requires: fragment-contract, script-scope, island-runtime, island-component, nav-islands

```
<x-zubzet::island src="/orders/table" nav url="mirror" key="orders" persist="session"/>
{{-- host URL becomes /dashboard?orders.page=3&orders.sort=-total --}}
// controller: nothing, the component seeds src from request()->getGet('orders.*') on first render
```

**Magic:** Paginate or sort a table inside a dashboard, copy the URL and send it to a colleague: they land on page 3 sorted by total, server-rendered on first paint. Reload keeps it, and Back steps through island states. Leave through the sidebar and come back later, and the list is exactly as you left it.

**Mechanism:** Component (~15 LOC PHP): host query keys prefixed '<key>.' (key regex [a-z0-9_-]{1,32}) are merged into src's query through Island::describe, so the same guard applies. Client (~35 LOC): after nav swaps (never on `every` ticks), pushState with the prefixed params. popstate re-runs mirrored islands using the ETag cache plus a small per-URL HTML Map (max 20). persist stores only the query string in sessionStorage['z:list:'+path+':'+key]. When the host URL has no params, it is restored with one refresh and replaceState. ~20 LOC more.

**Risk:** Duplicate keys collide (warn in test mode). Restored filters must be visible, which they are because the server renders the inputs, and a reset link clears them. Persisting costs one extra request on load. Only the query string is mirrored, never the path.

**Simplicity rationale:** It needs the full island stack plus nav islands, and its own part brings pushState/popstate, an HTML cache and sessionStorage restore, so the history-state edge-case surface is large.

**Judges:**
- Shareable list URLs matter a lot, but popstate, prefix merging and the persist restore add up. Persist could be dropped from v1.
- History management is the classic bug source: popstate ordering, an HTML cache, replaceState on restore, key collisions, and interaction with polling. Persisted filters surprise users. Mirrored params must pass through the same src guard. Ship the mirror without persist, if at all.

### 35. Pre-render 304: $res->notModified(...$versionParts)

Score 5.72 · impact 8 · simplicity 4 · wow 4.5 · 488 LOC from scratch (5 own) · requires: fragment-contract, script-scope, island-runtime, island-component, etag-precheck

```
public function action_kpis($req, $res) {
    $v = model("Orders")->exec("SELECT MAX(updated_at), COUNT(*) FROM orders")->resultToLine();
    if($res->notModified($v)) return;   // 304, nothing below runs
    view("dashboard/kpis", model("Orders")->expensiveKpis());
}
```

**Magic:** A 5-second poll on a heavy dashboard costs one indexed SELECT instead of the whole controller plus render. This fixes the design's admitted weak spot ('the 304 saves bandwidth, not CPU') with one line.

**Mechanism:** Response::notModified(...$parts): returns false when !isIsland() (drop-in). The ETag is W/"v-".sha1(json_encode([$parts, user id, fragment, path+query])). Island::etagMatches decides 304 + marker. Otherwise it sends the ETag and sets Island::$etagLocked so respond() does not overwrite it with the HTML hash. ~30 LOC PHP, zero JS.

**Risk:** A forgotten dependency makes the tile stale (user, fragment and URL are mixed in automatically; the rest is documented). The 'v-' namespace prevents collisions with HTML-hash ETags.

**Simplicity rationale:** This idea is essentially the etag-precheck block. It only pays off when an island client polls with Z-Island and If-None-Match, so from plain develop it carries the whole fragment contract and island runtime.

**Judges:**
- Fixes the admitted CPU weak spot of polling with one line. Essential for making every= responsible.
- Fixes the core weakness of the design with little code and no JS. Mixing in user id, fragment and URL is essential, and so is the permission check before notModified, or a 304 can confirm existence. A forgotten dependency means stale tiles.

### 36. Resilient save: Ctrl+S, save-state attribute, offline queue and replay

Score 5.72 · impact 5 · simplicity 6 · wow 6.5 · 95 LOC from scratch (70 own) · requires: zform-lifecycle

```
Z.Forms.create({dom:'settings', formAction:'settings', resilient:true});
CSS: form[data-z-save-state=offline] .save-hint::after { content: 'Offline, will save when back'; }
```

**Magic:** Ctrl/Cmd+S saves the form you are in. The form exposes dirty/saving/saved/offline/error states for CSS. When Wi-Fi drops mid-save on a train, it shows 'will save when back online', saves automatically when the connection returns and shows a success toast, without creating a duplicate.

**Mechanism:** One document keydown listener (mod+s) finds the owning ZForm through a WeakMap and calls send(). _setState() writes data-z-save-state at the existing hook points. On a network failure (status 0 or !navigator.onLine, never 4xx/5xx), the request {url, FormData snapshot, idempotency key} is kept in an in-memory queue (no persistence) and replayed in order once on the `online` event, re-reading the CSRF cookie at replay time. The queued banner has a Cancel button. Replay is safe because of _zonce/firstSubmit. ~45 LOC.

**Risk:** Opt-in, so the Ctrl+S hijack never surprises existing apps. Without firstSubmit() a lost response could double-submit, so replay only for forms using it or set-style updates. Closing the tab while offline loses the queue; pair it with draft:true.

**Simplicity rationale:** It needs no islands, but develop's ZForm.send has no state hooks and no idempotency key (no _zonce/firstSubmit), so safe replay also needs a server-side dedupe. Offline detection, refreshing the CSRF token on replay and ordered replay all add risk.

**Judges:**
- Ctrl+S and the state attribute are cheap wins. The offline queue is rarely needed for office admin panels and depends on firstSubmit. Split it and ship the first half.
- Three features bundled together. Offline replay of POSTs is risky without firstSubmit everywhere, because of double writes and replay after the session or user changed (re-check the user before replay). Ctrl+S and the save-state attribute are the cheap, safe parts. Ship those separately and drop the queue.

### 37. Module widget slots: <x-zubzet::widgets area="dashboard"/>

Score 5.68 · impact 6 · simplicity 4 · wow 7.5 · 498 LOC from scratch (40 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
// modules/billing/app/widgets.php
return [
  ["area" => "dashboard", "src" => "/billing/usage", "can" => "billing.view", "every" => "60s", "order" => 20],
];
{{-- app dashboard, written once --}}
<div class="row"><x-zubzet::widgets area="dashboard" class="col-md-4"/></div>
```

**Magic:** Run composer require on a module and its tile appears on the app's dashboard, permission-filtered per user and live-polling, without touching any app view. Remove the module and the tile is gone.

**Mechanism:** New Registry kind 'widgets' (one row in Kinds.php for <root>/app/widgets.php, userspace z_widgets.php first). The component merges, filters by area and can, sorts stably by order then module name, and renders one <x-zubzet::island> per entry, so Island::describe validates every module-supplied src. ~40 LOC PHP.

**Risk:** The trust boundary is composer install (existing module rule). Apps need a widgets_disabled config key to turn off a tile.

**Simplicity rationale:** The Registry/Kinds module system already exists in develop, so the slot itself is ~40 LOC of PHP, but each widget renders as an island, so it needs the island component and the whole island stack.

**Judges:**
- Strong for the module ecosystem. Fits the existing Registry kinds cleanly.
- Fits the module Registry nicely. Every module src goes through describe(). Needs a disable switch and stable ordering. Depends on module system PR #191.

### 38. Copy to clipboard with feedback

Score 5.65 · impact 5 · simplicity 9.0 · wow 2 · 29 LOC from scratch (29 own)

```
<code id="api-key">{{ $key }}</code>
<button data-z-copy="#api-key" class="btn btn-sm btn-outline-secondary">Copy</button>
<x-zubzet::copy :text="$inviteUrl">Copy invite link</x-zubzet::copy>
```

**Magic:** With one attribute the button copies, flips to a check mark and 'Copied' for 1.5 s and announces it to screen readers. It even works on old browsers and plain-HTTP intranets.

**Mechanism:** A delegated click on [data-z-copy]/[data-z-copy-text]. The target is resolved with querySelector (errors caught), and the text is input.value or textContent. Uses navigator.clipboard.writeText in secure contexts, otherwise a hidden textarea + execCommand fallback. Feedback is a Z.Lang text swap plus a polite live region. ~25 LOC JS, ~4 lines Blade.

**Risk:** Copying is user-initiated only, never on load. Text only, no HTML.

**Simplicity rationale:** A delegated Z.js click handler with an execCommand fallback plus a tiny Blade component; nothing from the catalog is needed.

**Judges:**
- Trivial and useful, not magic.
- Trivial and safe (user gesture, text only). Not magic, just a nice utility. The querySelector target is attribute-sourced but only reads text.

### 39. Declarative CRUD island from a controller property

Score 5.65 · impact 6.5 · simplicity 3 · wow 8 · 724 LOC from scratch (180 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, zform-island, nav-islands

```
class TodoController extends Controller {
    use \ZubZet\Framework\Island\Crud;
    protected array $crud = [
        "table" => "todo", "pk" => "id", "permission" => "todo.edit",
        "fields" => ["title" => "text|required|max:120", "done" => "bool"],
    ];
}
<x-zubzet::island src="/todo/crud"/>
```

**Magic:** A full list with inline add, edit and delete appears from a five-line property: server-validated, permission-checked, CSRF-protected and refreshing in place. The admin screen every project rewrites becomes one tag.

**Mechanism:** A trait providing action_crud. GET renders a framework view listing rows (with listState pagination). POST action=save validates via validateForm and writes via insertOrUpdateDatabase; action=delete goes through the CSRF-covered marker. Responses use the fluent refresh/toast effects. Identifiers come only from the controller property and are checked against ^\w+$. ~140 LOC (trait ~70, two Blade views ~70).

**Risk:** At the size limit, with scope creep toward z_admin: keep field types to text/number/bool/select. Delete needs CSRF enforcement. Per-row permissions are out of scope (overridable canEdit($row)).

**Simplicity rationale:** It needs nearly every island block, plus a trait, two views, and CSRF and listState helpers that develop lacks, with SQL-identifier and permission security surface.

**Judges:**
- High scope-creep risk at the size limit, and real apps outgrow it fast. Defer until the primitives settle.
- At or over the budget, with scope creep toward an admin generator. Mass assignment, identifier injection (the regex helps), per-row authorization gaps and delete CSRF are the pitfalls. Maintenance burden for every future field-type request. Defer.

### 40. Server-steered cadence: pollEvery / stopPolling (Z-Island-Every)

Score 5.62 · impact 6.5 · simplicity 4 · wow 6.5 · 483 LOC from scratch (25 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
$res->islandEvery('1s');     // job running, poll fast
$res->islandEvery('60s');    // idle, back off
$res->islandStop(redirect: '/export/download?id='.$job);   // done: stop, optionally move on
```

**Magic:** The controller decides how live a widget is from the data itself: a deploy tile polls every second during a deploy, every minute when idle, and stops for good when finished. A background job's progress bar goes silent and starts the download the moment it completes, while the Blade tag never changes.

**Mechanism:** Response header Z-Island-Every: <duration>|stop, parsed by the same parser and clamp (2000..3600000) as the every attribute, on both server and client. It is sent before the ETag check so 304s carry it too. The client overrides st.everyMs for the next tick. 'stop' swaps the final body, clears the timer and sets data-z-state=done, and follows Z-Island-Redirect if present. refresh-on triggers and Z.Islands.refresh() wake a stopped island. ~10 LOC PHP, ~15 LOC JS.

**Risk:** Client-side clamp prevents hot loops from buggy controllers. 'stop' must not be confused with error stops (403/404).

**Simplicity rationale:** It is a small header and a timer override, but it changes how polling islands behave, so the full fragment contract, island runtime, scope runner and island component (for the interval parser) are prerequisites.

**Judges:**
- Tiny, and it enables task progress and the notifier. Core building block.
- Tiny, and it reuses the existing parser and clamp. The redirect must go through the existing same-origin check. A clean primitive that unlocks the task features.

### 41. Zero-code DB-write triggers (refresh-on="db.orders")

Score 5.6 · impact 5.5 · simplicity 4 · wow 8 · 519 LOC from scratch (20 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component

```
// config: island_db_triggers = 1
// controller: nothing new, any existing write
model("Orders")->exec("UPDATE orders SET state = ? WHERE id = ?", "si", $s, $id);
return $res->success();
// view
<x-zubzet::island src="/orders/table" refresh-on="db.orders"/>
```

**Magic:** Existing controllers that never heard of islands make the right widgets refresh: an UPDATE on orders anywhere in the request refreshes every island subscribed to db.orders. Optionally it also bumps the version counter for cross-user freshness, with zero app code.

**Mechanism:** In Connection::exec, after execWithRecovery, when the config is on and the request is XHR/island, a regex (INSERT [IGNORE] INTO|REPLACE INTO|UPDATE|DELETE FROM) collects table names in a static set. Each name is emitted once as Island::header('Z-Island-Trigger', 'db.'.$t, append), and the step is skipped when headers_sent(). Framework tables (z_login, logs) are ignored. ~20 LOC PHP, zero JS.

**Risk:** The regex misses CTEs, multi-table UPDATE/JOIN and procedures (document; explicit triggers still work). It exposes table names to the client, hence opt-in.

**Simplicity rationale:** There are only ~20 LOC in Connection::exec, but they emit Z-Island-Trigger headers, which only matter with response-effects and refresh-on islands (the full stack); detecting writes by regexing SQL is a heuristic with edge cases.

**Judges:**
- Magical, but regex SQL parsing is leaky and exposes table names. Fine as opt-in.
- Regex SQL parsing in Connection::exec, the hottest path, is brittle (CTEs, comments, quoted identifiers, multi-table). It leaks schema table names to clients, so it must stay opt-in. Magical, but a maintenance liability.

### 42. Bulk select with native form= attribute

Score 5.58 · impact 7 · simplicity 4 · wow 5.5 · 574 LOC from scratch (75 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component

```
<x-zubzet::bulk id="orders-bulk" post="/orders/bulk" refresh="#orders">
  <select name="op"><option value="archive">Archive</option></select>
  <button data-z-confirm="Apply to selected?">Apply</button>
</x-zubzet::bulk>
<x-zubzet::island id="orders" src="/orders/table" nav/>
{{-- in the table view --}}
<input type="checkbox" form="orders-bulk" name="ids[]" value="{{ $o->id }}">
// controller
foreach($req->ids('ids') as $id) $this->model('Order')->archiveIfAllowed($id, user());
$res->islandTrigger('orders.changed');
```

**Magic:** Check rows inside a paginated, polled island and a toolbar outside it shows '3 selected'. Shift-click selects ranges, select-all toggles the page, and Apply posts once and refreshes the table. Checkboxes join the outer form through the HTML form= attribute, so it also works without JS.

**Mechanism:** The component renders <form id method=post action> with CSRF::field(), a count badge and a select-all box. JS (~45 LOC): delegated change on [form=id] updates the count, shift-click selects ranges, and a selected-id Set in sessionStorage restores checks after swaps (on z:island:loaded). Submit goes through fetch with Z.Request._csrfHeaders() and then the response pipeline. Request::ids() (~5 LOC) returns positive ints only.

**Risk:** Needs CSRF enforcement on the target (posts with the ZForm marker so the marker-gated check applies). Per-row permissions belong in the model. Cross-page selection shows 'N selected (incl. other pages)' with a clear button.

**Simplicity rationale:** The bulk JS is small, but refresh and trigger rely on the whole island stack, and develop has no CSRF helper, so own code includes the token plumbing.

**Judges:**
- Bulk actions are everywhere in admin UIs. The form= trick is clever, but range select, the sessionStorage set and cross-page state add up.
- Combines a component, shift-range selection, sessionStorage selection persistence across pages, and fetch submission. Cross-page selection is confusing. The target must enforce CSRF and per-row authorization. Request::ids() is a good tiny helper on its own.

### 43. Click-to-edit anywhere

Score 5.55 · impact 6 · simplicity 4 · wow 7 · 560 LOC from scratch (45 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, zform-island

```
<x-zubzet::editable edit="/user/edit-name?id={{ $u->id }}" show="/user/name-chip?id={{ $u->id }}">
  {{ $u->name }}
</x-zubzet::editable>
{{-- the edit route is any existing ZForm view --}}
```

**Magic:** Any piece of text on any page becomes editable in place. Click it and the real server-rendered edit form appears right there with server validation; save it and the updated display version comes back. No JS and no route convention are needed.

**Mechanism:** The component renders the slot in <div data-z-editable role=button tabindex=0 data-z-edit data-z-show> (both validated via Island::describe). Click or Enter turns it into a non-polling island with src=edit. On a bubbling z:saved from the inner ZForm, src becomes show and it refreshes once; without show it restores the cached slot HTML. Escape cancels. ~15 LOC Blade, ~30 LOC JS on top of ZForm-in-island.

**Risk:** Writes go through ZForm (CSRF-covered). Permission is enforced by the edit route, and a 403 shows the error state. Nested editables inside an open form are ignored.

**Simplicity rationale:** Small component + JS on its own, but it turns elements into islands hosting a ZForm, so it requires the island runtime, component and ZForm-in-island integration.

**Judges:**
- Reuses ZForm-in-island well. Edge cases around nested editables and cancel.
- Depends on ZForm-in-island and a z:saved event. State transitions (escape, nested, error) add edge cases. Writes go through ZForm with CSRF and route permissions, so it is secure if the edit route checks.

### 44. Swap that keeps focus, caret and inner scroll

Score 5.53 · impact 7.5 · simplicity 4 · wow 4.5 · 463 LOC from scratch (30 own) · requires: fragment-contract, script-scope, island-runtime, swap-hooks

```
{{-- automatic for every island; elements only need a stable id, name or data-z-key --}}
<div class="overflow-auto" style="max-height:300px" data-z-key="log">...</div>
```

**Magic:** A polled table inside a scrollable panel refreshes every 5 s, but the panel's scroll position, the button you tabbed to and the caret in a search box all stay put. The refresh is invisible apart from the data.

**Mechanism:** Before replaceChildren, record document.activeElement's identity (id, else name, else data-z-key) with selectionStart/End, plus scrollTop/Left of scrolled descendants, only if focus was inside the island. After the swap, query the same keys (CSS.escape), restore scroll, focus({preventScroll:true}) and setSelectionRange. If the element vanished, focus the island (tabindex=-1) so keyboard users are not dumped on <body>. ~30 LOC.

**Risk:** Elements without stable keys cannot be matched (document the convention). It must not steal focus that was outside the island.

**Simplicity rationale:** Only meaningful inside the island swap and plugs in through the swap hooks (like permanent elements and keyed swaps), so from develop it carries the whole island stack; once that exists it is about 50 LOC with some focus/selection browser quirks.

**Judges:**
- Prerequisite quality for any polled island and for live search. Should be in core.
- Should simply be the default swap behaviour. Small and well-scoped, with CSS.escape on lookups. Some input types (number, email) throw on setSelectionRange, so wrap it in try.

### 45. Self-healing after deploy (version handshake)

Score 5.43 · impact 6 · simplicity 4 · wow 6.5 · 448 LOC from scratch (35 own) · requires: fragment-contract, script-scope, island-runtime

```
Nothing to write. Every island response carries Z-Version: <assetVersion>; head prints Z.version. Optional: $res->islandVersion('2026.10.23');
```

**Magic:** After a deploy, every open dashboard quietly picks up the new JS and CSS. Background tabs reload themselves unseen, a visible tab with no unsaved changes reloads when it is next hidden, and dirty forms get a 'New version available, reload' toast. Stale tabs stop posting old payloads to new controllers.

**Mechanism:** Island::respond adds the header from the same assetVersion as generateResourceLink. Z.Islands compares it with Z.version on every 2xx/304 and dispatches a cancelable z:version event. The default handler reloads when hidden, waits for visibilitychange, or shows a flash when the dirty flag is set. Disabled when the version is time-based (dev mode). A sessionStorage guard prevents reload loops during rolling deploys. ~10 LOC PHP, ~25 LOC JS.

**Risk:** Must be off in dev mode (time()-based versions). Mixed versions across nodes during a rolling deploy could flap (loop guard). Never reload while dirty.

**Simplicity rationale:** Own code is tiny (header + compare + reload guard) and assetVersion already exists in develop, but it only fires on island responses, so it drags in the full fragment contract and island runtime.

**Judges:**
- Prevents stale-JS bugs after deploys. The loop guard and the dev-mode switch-off are mandatory.
- Auto-reloading user tabs is risky: data loss if the dirty detection misses non-ZForm inputs, and flapping during rolling deploys. Must be off in dev (time-based versions). Prefer a toast over silent reload by default.

### 46. Presence: 'Anna is also viewing / editing'

Score 5.38 · impact 5.5 · simplicity 3 · wow 8.5 · 593 LOC from scratch (110 own) · requires: fragment-contract, script-scope, island-runtime, island-component, etag-precheck

```
<x-zubzet::presence :key="'order:'.$order->id" />
<x-zubzet::presence :key="'order:'.$order->id" editing />
```

**Magic:** Avatars or a line saying 'Anna is editing this order' appear on a plain server-rendered page and disappear when she leaves, Google Docs-style.

**Mechanism:** Migration z_presence(key, user_id, editing, seen_at, PK key+user). The component wraps an island to core route /_zubzet/presence with an HMAC-signed key (visible only to users who could render the page), every 10 s. Each poll upserts the caller (skipped if seen_at is under 20 s old) and renders others seen in the last 30 s, and an unchanged list is a 304. 'editing' flips on the first input event. pagehide sends a sendBeacon to remove the row. ~80 LOC PHP/Blade plus migration.

**Risk:** One write per user per 20 s per watched page, plus a cleanup (opportunistic DELETE of rows older than 5 min). Show names only within the same organisation. Signed keys prevent record enumeration.

**Simplicity rationale:** Polling-island based, plus a migration, core-route registration (not in develop), HMAC-signed keys, sendBeacon cleanup and write-on-poll state.

**Judges:**
- Writes, a migration, cleanup and signed keys add up to M. The lost-update guard covers the real problem more cheaply.
- A migration, a write per user per poll (Galera write load), cleanup, sendBeacon unreliability, and HMAC-signed keys. Leaks names across tenants unless scoped. Too much for the core right now.

### 47. Infinite scroll by self-replacing sentinel (swap="outer")

Score 5.35 · impact 5.5 · simplicity 4 · wow 7 · 471 LOC from scratch (13 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
{{-- orders/feed.blade.php, also a normal paginated page --}}
@foreach($rows as $r) <x-order-card :order="$r"/> @endforeach
@if($hasMore)
  <x-zubzet::island :src="'/orders/feed?page='.($page+1)" lazy swap="outer">
    <div class="spinner-border"></div>
  </x-zubzet::island>
@endif
```

**Magic:** Infinite scroll with zero JS and no append API. The last element of each page is an island for the next page; when it scrolls into view, it replaces itself with the next batch plus a new sentinel. Without JS the noscript link is a real next-page link.

**Mechanism:** Builds on lazy (IntersectionObserver) plus one enum value: swap=outer does el.replaceWith(template.content). The MutationObserver starts the nested sentinel. The recursion guard compares the full src including the query, so the same path with a different page is allowed. ~10 LOC JS, ~3 LOC PHP.

**Risk:** Unbounded DOM growth on long feeds (optional max-pages). every must be rejected with swap=outer, and an identical src is still rejected.

**Simplicity rationale:** One enum value on top of lazy islands, which is trivial once islands exist. From plain develop it needs the lazy island runtime, the component and the fragment endpoint contract.

**Judges:**
- Elegant and tiny. Infinite scroll matters less in admin tools than paging.
- Elegant reuse of lazy plus one swap mode. Watch for DOM growth, and for a duplicate fetch if the observer fires twice before replaceWith. Reject every with outer. The noscript fallback is a nice touch.

### 48. Global 'your job finished' notifier

Score 5.2 · impact 6 · simplicity 3 · wow 7 · 604 LOC from scratch (50 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, task-status-endpoint

```
Nothing in the app view. Task: Tasks::dispatch(new ExportOrders(...))->notify('Export ready', '/exports/{id}');
```

**Magic:** Start a long export, navigate anywhere in the app, and a toast 'Export ready - Download' appears on whatever page you are on when the worker finishes.

**Mechanism:** body.blade.php renders <x-zubzet::island src="/_zubzet/tasks/mine" every="10s"/> only when the user has pending notify tasks (one indexed query). The route returns an empty body plus Z-Island-Flash (and Z-Island-Notify) for newly terminal tasks, acks them atomically (conditional UPDATE + affectedRows) and sends Z-Island-Every: stop when nothing is pending. ~50 LOC PHP.

**Risk:** Adds one indexed query (user_id, notified=0) per logged-in render. The atomic ack prevents double toasts across tabs. Depends on PR #199.

**Simplicity rationale:** Needs the full island stack, flash effects and the core task endpoint, plus the PR #199 task queue which is not in develop, and atomic ack state on the server.

**Judges:**
- Useful but adds a query per render and atomic acks. Ship after task progress.
- Adds an indexed query to every logged-in render, and an island in body.blade.php (a change to the global layout). The atomic ack is the correct approach. Depends on #199. Notification paths must be validated.

### 49. Permission-aware islands: can= and hideIsland()

Score 5.12 · impact 6.5 · simplicity 4 · wow 4.5 · 478 LOC from scratch (20 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
<x-zubzet::island src="/admin/revenue" can="admin.revenue" every="30s" on-forbidden="hide">
  <x-slot:denied><small>Ask an admin for revenue access</small></x-slot:denied>
</x-zubzet::island>
// any island action
if(!$req->checkPermission("beta.feature", true)) return $res->hideIsland();
```

**Magic:** One dashboard template serves every role: tiles a user cannot see are never rendered, never fetched and never show a 403 box. If permissions are revoked mid-session, the tile quietly disappears on the next poll.

**Mechanism:** Component: if can is set and user()->checkPermission fails, render the denied slot (or nothing) and stop before emitting data-z-*. Response::hideIsland() sends 204 + Z-Island-Hide: 1 and exits; the client removes and stops the island on that header, or on 403 when on-forbidden=hide. ~12 LOC PHP, ~8 LOC JS.

**Risk:** can= is presentation only and the target route must still check (docs must say so loudly). Super-user semantics must match checkPermission.

**Simplicity rationale:** Tiny on its own (a component guard, Response::hideIsland and a client header check), but it extends the island component and runtime, so from develop it pulls in the full island stack.

**Judges:**
- One template for every role is very practical for dashboards. Docs must stress that the route still checks.
- Small and useful. The danger is that devs treat can= as authorization. Docs must state that the target route enforces it. Behaviour on 403 must not leak tile existence beyond what the template shows anyway.

### 50. Permanent elements across swaps

Score 5.1 · impact 5.5 · simplicity 4 · wow 6 · 445 LOC from scratch (12 own) · requires: fragment-contract, script-scope, island-runtime, swap-hooks

```
<video id="cam" data-z-permanent src="..."></video>
<canvas id="chart" data-z-permanent></canvas>   {{-- inside any island view --}}
```

**Magic:** A playing video, a Chart.js canvas, a map or a third-party widget inside a polling island keeps running through every refresh while the surrounding text updates.

**Mechanism:** Before the swap, collect [data-z-permanent][id] in the old tree. In the new template content, replace each element with the same id by the live old node (replaceWith moves it). ~12 LOC in the swap function. It works with plain replace, so no morph is needed.

**Risk:** If the id is missing from the new tree the element is dropped normally. Server-side attribute changes to a permanent element are ignored by design.

**Simplicity rationale:** About 12 lines in the swap function, but there is no swap to hook into in plain develop, so it needs the island runtime and its server contract.

**Judges:**
- About 12 lines, solves chart and video resets. Cheap, so include it.
- About 12 lines with a clear contract. Moving nodes can reset iframe state in some browsers (iframes reload on move), so document that. No security impact.

### 51. View Transitions on island swaps

Score 5.0 · impact 4 · simplicity 4 · wow 8 · 501 LOC from scratch (23 own) · requires: fragment-contract, script-scope, island-runtime, island-component, swap-hooks

```
<x-zubzet::island src="/kanban/board" refresh-on="card.moved" transition/>
<div class="card" data-z-key="card-{{ $c->id }}">...</div>
```

**Magic:** When a card moves from 'Todo' to 'Done' after a server refresh, it visibly glides to its new column and numbers crossfade, instead of the board flickering. There is no client state and no diffing library, only a flag.

**Mechanism:** In the swap: if data-z-transition is set, document.startViewTransition exists and reduced motion is off, wrap the swap in startViewTransition(). Before and inside the callback, assign viewTransitionName = 'zk-' + sanitized data-z-key on the old and new elements so the browser pairs them. Concurrent swaps are batched into one transition per frame. Other browsers just swap. ~20 LOC JS.

**Risk:** Duplicate view-transition-names abort the transition (caught, then an instant swap). Keys are sanitized to [A-Za-z0-9_-].

**Simplicity rationale:** About 20 LOC wrapping the swap in startViewTransition through the swap hooks (which list view transitions as a client), but there is no swap to wrap without the whole island stack (contract, scope runner, runtime, component attribute).

**Judges:**
- Impressive demo, low practical impact. Graceful fallback.
- Progressive enhancement. Duplicate names abort the transition, and concurrent transitions interrupt each other. Batching per frame across islands adds subtlety. Visual polish only.

### 52. Freshness and offline awareness

Score 4.93 · impact 6 · simplicity 4 · wow 4.5 · 433 LOC from scratch (20 own) · requires: fragment-contract, script-scope, island-runtime

```
Automatic for every polled island. Style with CSS:
.z-island[data-z-stale]{opacity:.6}
.z-island[data-z-stale]::after{content: attr(data-z-age)}
```

**Magic:** When the Wi-Fi drops or the server stops answering, every live widget visibly greys out and says 'updated 3 min ago'. When the connection returns, they refresh at once. Users never trust stale numbers by accident.

**Mechanism:** Store lastOk on each 2xx/304. One global 15 s ticker sets data-z-stale when now - lastOk > 3*every and writes a relative age (Intl.RelativeTimeFormat) into data-z-age. On `online`, refresh all islands immediately and reset the backoff. ~20 LOC JS, no PHP.

**Risk:** Apps opt in visually through CSS. The ticker must be global, not one per island.

**Simplicity rationale:** Only ~20 LOC of Z.js ticker/online logic, but it only makes sense on polled islands, so from develop it carries the whole fragment contract, script scope and island runtime (~413 LOC).

**Judges:**
- Prevents trusting stale numbers. Tiny.
- Small, one global ticker, purely client-side. Prevents trust in stale numbers. CSS opt-in keeps it drop-in.

### 53. Stale-while-revalidate islands (instant back navigation)

Score 4.9 · impact 5 · simplicity 4 · wow 6 · 493 LOC from scratch (35 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
<x-zubzet::island src="/dashboard" fragment="kpis" lazy keep>
    <x-skeleton.tiles :n="4"/>
</x-zubzet::island>
```

**Magic:** Coming back to the dashboard shows the last KPIs instantly, with no spinner and no layout shift, subtly marked as refreshing, and then they update silently. It feels like a native app's cached screen.

**Mechanism:** Opt-in keep: after a successful swap, store {html, etag, height} in sessionStorage under 'zi:'+userKey+':'+src+'#'+fragment (try/catch, 100 KB cap). On init, an entry is inserted through the normal template/script pipeline with data-z-state=stale, then fetched with If-None-Match (often a 304). Even without keep, the stored height is applied as min-height to kill CLS. Also refreshes islands on bfcache pageshow. ~35 LOC JS.

**Risk:** User-specific HTML in sessionStorage, so clear zi:* on 401 and when the user marker changes (hash, never the raw id). Scripts run twice and must be idempotent (already required).

**Simplicity rationale:** Small sessionStorage cache code, but it only makes sense on top of the island fetch/ETag/swap pipeline and must avoid leaking cached user data.

**Judges:**
- Storing user HTML in sessionStorage brings invalidation and privacy chores. The min-height trick against layout shift is the cheap part worth taking.
- Stores user-specific HTML in sessionStorage. It must be purged on user change and on 401, and the XSS exposure grows. Scripts run twice. bfcache handling is a separate concern. Moderate value.

### 54. Opt-in desktop notifications from the server

Score 4.75 · impact 4 · simplicity 4 · wow 7 · 494 LOC from scratch (40 own) · requires: fragment-contract, script-scope, island-runtime, response-effects

```
Controller (in any island response): $res->islandNotify('Export ready', 'orders-2026-10.csv', '/exports/17');
Blade, once: <x-zubzet::notify-permission>Enable desktop alerts</x-zubzet::notify-permission>
```

**Magic:** A server-rendered app sends real OS notifications ('Your export is ready') while the user is in another app, with no service worker and no push server.

**Mechanism:** Z-Island-Notify header (JSON, ~512 bytes max: title/body/path). The runtime calls new Notification(title, {body, tag}) only when permission is granted and the document is hidden. A click focuses the window and navigates to the path after the same same-origin, root-prefixed check as Z-Island-Redirect. requestPermission() is only called from the button component (user gesture). The tag dedupes across tabs. ~30 LOC JS, ~10 LOC PHP.

**Risk:** Only fires while some tab is open and polling. Text goes through the Notification API (no HTML). Path validation is mandatory.

**Simplicity rationale:** It extends the island response-effect header pipeline and adds permission and same-origin path checks, so it cannot ship without island-runtime.

**Judges:**
- Only fires while a tab is open and polling, and users rarely grant permission in internal tools.
- Only works while a tab is polling. The click path needs strict same-origin validation. Permission prompts must be user-gesture-only. Notification spam risk across tabs, partly handled by the tag.

### 55. Live tab title and app badge

Score 4.7 · impact 4.5 · simplicity 4 · wow 6 · 488 LOC from scratch (30 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
Controller of any polled island: $res->islandBadge(count($unread));
or Blade: <x-zubzet::island src="/inbox/count" every="30s" badge="title app" every-hidden="60s"/>
```

**Magic:** The browser tab shows '(3) MyApp' and an installed PWA shows a dock or taskbar badge, updating while the tab sits in the background, from one PHP line.

**Mechanism:** A Z-Island-Badge: <int 0..9999> header (or, with the badge attribute, the island's textContent parsed as an int). The client rewrites document.title as '(n) ' + the stored original title and calls navigator.setAppBadge?.(n)/clearAppBadge. Badge islands only keep a slow hidden-tab poll (every-hidden, minimum 60 s), carried by the cross-tab leader when present. ~6 LOC PHP, ~20 LOC JS.

**Risk:** Integer-only parsing, so nothing can be injected into the title. Breaks 'pause when hidden', hence opt-in with a higher floor. Must not fight apps that set the title themselves.

**Simplicity rationale:** The badge itself is ~26 LOC, but it only makes sense on a polled island, so from plain develop it drags in the whole island stack.

**Judges:**
- Nice for inbox-style apps. Opt-in hidden polling.
- Integer-only parsing is safe. Breaks pause-when-hidden (server cost). Fights apps that manage document.title. Niche.

### 56. Hover-prefetch for island navigation

Score 4.58 · impact 4.5 · simplicity 4 · wow 5.5 · 517 LOC from scratch (30 own) · requires: fragment-contract, script-scope, island-runtime, island-component, nav-islands

```
<x-zubzet::island src="/orders/table" nav prefetch/>
{{-- or per link inside the fragment --}}
<a href="/orders/table?page=4" data-z-prefetch>4</a>
```

**Magic:** Pagination and sort clicks inside islands feel instant: the next page is fetched during the 100-300 ms between hover and click, and the click swaps from memory with no spinner.

**Mechanism:** After 80 ms of pointerenter/focusin intent on a nav link the island would intercept anyway, issue the normal island GET and cache the Promise in a Map (10 s TTL, max 10). Click consumes it or fetches. Skipped on saveData and touch. The cache is cleared after any POST or trigger. ~30 LOC.

**Risk:** Prefetch is a real GET, so state-changing GETs (logout) must never qualify: only intercepted links, plus a data-z-native denylist. It adds some server load.

**Simplicity rationale:** Prefetching only matters for nav islands, so from develop it brings in the full island runtime and nav stack; on top of that it is about 30 LOC of cache code.

**Judges:**
- Nice polish. Watch for GETs that change state and for cache invalidation.
- Cache invalidation after writes and triggers is the tricky part, and stale data can be shown. State-changing GETs in consumer apps make this risky. Marginal gain over a fast server.

### 57. Server-Timing island inspector (test mode)

Score 4.58 · impact 4.5 · simplicity 4 · wow 5.5 · 453 LOC from scratch (40 own) · requires: fragment-contract, script-scope, island-runtime

```
// nothing to write; with execution_type=test every island response carries
// Server-Timing: ctrl;dur=18, db;dur=11;desc="7 queries", view;dur=3
// and each island gets a hover badge: "kpis · 7q · 32ms · 12/15 304"
```

**Magic:** DevTools Network natively shows every poll's controller, query and render time, and each island wears a tiny badge with query count and 304 hit rate. The cost of every="2s" becomes visible before it ships.

**Mechanism:** Island::respond, only when DebugBarBridge::isEnabled(), sends a standard Server-Timing header from request()->getExecutionTime() and a static query counter/duration summed next to DebugBarBridge::collectQuery in Connection::exec. When the header exists, Z.js writes data-z-debug onto the island (CSS ::after badge, absolute, pointer-events none) and logs with console.debug. ~20 LOC PHP, ~20 LOC JS.

**Risk:** Strictly gated on test mode so production never leaks timings or query counts. The badge must not shift layout.

**Simplicity rationale:** It hooks Island::respond and the island client for badges; DebugBarBridge and getExecutionTime already exist in develop, but the island stack itself is most of the cost.

**Judges:**
- Good developer feedback on polling cost. Strictly test-mode gated.
- Developer-only. It must be strictly gated so production never leaks timings or query counts. Adds a static counter in Connection::exec.

### 58. One poller for all tabs (Web Locks leader)

Score 4.35 · impact 4.5 · simplicity 3 · wow 6 · 574 LOC from scratch (50 own) · requires: fragment-contract, script-scope, island-runtime, response-effects, island-component, broadcast-relay

```
Nothing; automatic for every polling island. Opt out: <x-zubzet::island src="/stats" every="5s" shared="false"/>
```

**Magic:** A user with 12 dashboard tabs open costs the server one poll stream instead of 12, and all tabs update at the same instant.

**Mechanism:** Key = rootPath + src + fragment + auth flag + user hash. Each tab calls navigator.locks.request('z-island:'+key); only the holder polls and posts {key, etag, html} on BroadcastChannel. Followers swap through the normal pipeline (script rules, dirty-form guard). The lock passes automatically when the leader closes, and a visible follower can request a tick when the leader is hidden. Falls back to per-tab polling without Web Locks. ~50 LOC.

**Risk:** After logout/login in the same profile, messages must be dropped (key includes the user hash). Followers must keep the 'no swap while editing' guard. Ship after the trigger relay.

**Simplicity rationale:** It optimises polling islands, so it brings in the full island runtime plus the cross-tab relay. Leader handoff, per-user keys (so one user's HTML never reaches another session) and the fallback path add state and security edge cases.

**Judges:**
- Saves server load, but leader handoff and the follower swap path are subtle. Defer until polling cost is proven.
- Distributed-systems logic in the browser: leader handoff, hidden leaders, user changes, and HTML broadcast between tabs that may have different auth state. Server-load benefit only. Pre-render 304 gives most of the savings more simply.

### 59. Island-only actions: island_<name> methods

Score 4.28 · impact 5 · simplicity 4 · wow 3.5 · 464 LOC from scratch (6 own) · requires: fragment-contract, script-scope, island-runtime, island-component

```
class ShopController extends Controller {
    public function action_cart($req, $res) { view("shop/cart"); }          // full page
    public function island_cart($req, $res) { view("shop/cart_mini"); }     // same URL, island request
    public function island_badge($req, $res) { view("shop/badge"); }        // widget-only endpoint
}
<x-zubzet::island src="/shop/badge" refresh-on="cart"/>
```

**Magic:** Declaring a widget endpoint is just naming a method. The same URL serves the page to browsers and a compact widget to islands, and widget-only endpoints cannot be opened as broken chrome-less pages.

**Mechanism:** In Router::executeControllerAction, before method_exists: if isIsland() and island_<action> exists, call it instead. Non-island requests never look at the prefix and fall through to action_/fallback/404 as today. ~6 LOC.

**Risk:** Only file-routed actions (explicit Routes need ->island() later). Permission checks must be repeated in island_ methods. Grep consumer apps for existing island_* methods first.

**Simplicity rationale:** A 6-line Router branch, but it is only reachable via island requests (isIsland from the fragment contract, issued by the island runtime/component), so from develop it costs the whole island stack.

**Judges:**
- Six lines, clean convention. Duplicated permission checks are a footgun.
- Trivial. The main risk is that existing apps may already have island_* methods that become reachable, so grep the consumer apps first. Permission checks are duplicated by hand. A Z-Island header request can trigger alternative code paths, so it must not bypass action-level guards.

### 60. Animated number counters on change

Score 4.1 · impact 3 · simplicity 4 · wow 6 · 524 LOC from scratch (46 own) · requires: fragment-contract, script-scope, island-runtime, island-component, swap-hooks

```
<x-zubzet::island src="/stats/orders" every="5s"/>
{{-- inside the stats view --}}
Open orders: <x-zubzet::count key="open-orders" :value="$open"/>
Revenue: <x-zubzet::count key="rev" :value="$revenue" format="currency:EUR"/>
```

**Magic:** When a polled tile changes from 41 to 44, the number rolls up smoothly and flashes green (red when it drops), like a trading dashboard, from a static view that echoes a number.

**Mechanism:** The component renders <data value data-z-count=key data-z-format> with server-formatted text (correct without JS). Before a swap the runtime records values by key; after it, changed keys are tweened with requestAnimationFrame over 600 ms using Intl.NumberFormat, with the format parsed from an enum regex (number|percent|currency:XXX), toggling .z-up/.z-down. Skipped under reduced motion, and tweens are cancelled via a WeakMap of rAF ids. ~40 LOC JS, ~6 LOC Blade.

**Risk:** The format attribute is an enum and never passed raw to anything executable. aria-live announces only the final value.

**Simplicity rationale:** The counter code is tiny (about 40 JS + 6 Blade lines), but it only does anything once polling islands exist, so from plain develop it pays for the whole island stack plus a before/after swap hook.

**Judges:**
- Eye candy for dashboards. Low business value.
- Mostly a gimmick. The format enum is fine. rAF cancellation and reduced-motion handling are fine. Low priority.

