{{-- Experimental. A link that opens any route of this application in a modal, server rendered
     without its layout. Without JS it is a normal link to the full page. A ZForm inside closes
     the modal after saving.
     <x-zubzet::modal href="/user/edit?id=4" title="Edit user">Edit</x-zubzet::modal> --}}
@props(['href' => null, 'title' => '', 'size' => null])
@php $modal = \ZubZet\Framework\Rendering\Island::describe($href, null, null, null); @endphp
<a href="{{ $modal->url }}" data-z-modal="{{ $modal->url }}" data-z-modal-title="{{ $title }}" data-z-modal-size="{{ in_array($size, ['sm', 'lg', 'xl'], true) ? $size : '' }}" {{ $attributes->whereDoesntStartWith('data-z-') }}>{{ $slot }}</a>
