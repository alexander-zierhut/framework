{{-- Experimental. A button that POSTs to a route of this application without any form or JS.
     Inside an island it refreshes that island afterwards; the controller may also answer with
     $res->islandTrigger("...") / $res->islandFlash("...").
     <x-zubzet::action post="/todo/delete" :data="['id' => $id]" confirm="Delete?">Delete</x-zubzet::action> --}}
@props(['post' => null, 'data' => [], 'confirm' => null, 'then' => 'refresh'])
@php $action = \ZubZet\Framework\Rendering\Island::describe($post, null, null, null); @endphp
<button type="button" data-z-action="{{ $action->url }}" data-z-data="{{ json_encode((object) $data) }}" data-z-confirm="{{ $confirm }}" data-z-then="{{ $then === 'none' ? 'none' : 'refresh' }}" {{ $attributes->whereDoesntStartWith('data-z-')->merge(['class' => 'btn']) }}>{{ $slot }}</button>
