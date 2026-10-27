{{-- Experimental. Loads any route of this application in place, server rendered without its
     layout, optionally polling and refreshing on server events. The slot is the placeholder.
     <x-zubzet::island src="/stats/orders" every="5s" fragment="kpis" lazy nav refresh-on="order.saved"/>
     nav: links and GET forms inside load in place (pagination, sorting, filters). --}}
@props(['src' => null, 'fragment' => null, 'every' => null, 'lazy' => false, 'nav' => false, 'live' => false, 'refreshOn' => null, 'scripts' => 'run'])
@php $island = \ZubZet\Framework\Rendering\Island::describe($src, $fragment, $every, $refreshOn); @endphp
<div data-z-island data-z-src="{{ $island->url }}" data-z-fragment="{{ $island->fragment }}" data-z-every="{{ $island->everyMs }}" data-z-lazy="{{ $lazy ? '1' : '0' }}" data-z-nav="{{ $nav ? '1' : '0' }}" data-z-refresh-on="{{ $island->refreshOn }}" data-z-scripts="{{ $scripts === 'none' ? 'none' : 'run' }}" data-z-auth="{{ user()->isLoggedIn ? '1' : '0' }}" @if($live) aria-live="polite" @endif {{ $attributes->whereDoesntStartWith('data-z-')->merge(['class' => 'z-island']) }}>{{ $slot }}<noscript><a href="{{ $island->url }}">{{ $island->url }}</a></noscript></div>
