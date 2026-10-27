@extends($layout)

@section("content")
<div class="card">
    <div class="card-header"><strong>Orders</strong></div>
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Customer</th><th class="text-right">Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($orders as $order)
            <tr data-test="order-{{ $order['id'] }}">
                <td>{{ $order["id"] }}</td>
                <td>{{ $order["customer"] }}</td>
                <td class="text-right">{{ number_format($order["total"], 0, ",", ".") }} €</td>
                <td>
                    @if($order["shipped"])<span class="badge badge-success">shipped</span>@endif
                    @if(!$order["shipped"])<span class="badge badge-warning">open</span>@endif
                </td>
                <td class="text-right text-nowrap">
                    <x-zubzet::action post="/magic/ship" :data="['id' => $order['id']]" class="btn-sm btn-outline-secondary" data-test="ship-{{ $order['id'] }}">
                        {{ $order["shipped"] ? "Unship" : "Ship" }}
                    </x-zubzet::action>
                    <x-zubzet::action post="/magic/delete" :data="['id' => $order['id']]" confirm="Delete order #{{ $order['id'] }}?" class="btn-sm btn-outline-danger" data-test="delete-{{ $order['id'] }}">
                        <i class="fas fa-trash"></i>
                    </x-zubzet::action>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-body border-top">
        <div id="order-form"></div>
    </div>
</div>
<script>
    var orderForm = Z.Forms.create({ dom: "order-form" });
    orderForm.createField({ name: "customer", type: "text", text: "Customer", width: 7 });
    orderForm.createField({ name: "total", type: "number", text: "Total €", width: 5 });
    orderForm.buttonSubmit.textContent = "Add order";
</script>
@endsection
