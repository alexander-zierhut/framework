@extends($layout)

@section("content")
<div class="card">
    <div class="card-header"><strong>Your profile</strong></div>
    <div class="card-body"><div id="profile-form"></div></div>
</div>
<script>
    var profileForm = Z.Forms.create({ dom: "profile-form" });
    profileForm.createField({ name: "name", type: "text", text: "Display name", value: {!! json_encode($name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!} });
    profileForm.buttonSubmit.textContent = "Save";
</script>
@endsection
