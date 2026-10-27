@extends($layout)

@section("content")
<div class="d-flex align-items-baseline justify-content-between">
    <h2 data-test="greeting">Good afternoon, {{ $name }} 👋</h2>
    <span class="text-muted small">every widget below is a normal server-rendered route ·
        <x-zubzet::modal href="/magic/profile" title="Edit profile" data-test="open-profile-modal">edit profile in a modal</x-zubzet::modal></span>
</div>
@endsection
