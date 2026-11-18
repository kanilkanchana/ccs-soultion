@extends('ccs.layout')
@section('title',__('Available Parts'))
@section('body-class','ccs-technician-page')
@section('content')
@include('ccs.technician.styles')
<div class="tech-heading"><div><h1>{{ __('Available Parts') }}</h1><p>{{ __('Stock availability for workshop repairs') }}</p></div><a class="btn secondary" href="{{ route('ccs.jobs',['scope'=>'current']) }}">{{ __('Current Jobs') }}</a></div>
<form class="tech-search" method="GET" action="{{ route('ccs.stock') }}"><input name="q" maxlength="150" value="{{ $search }}" placeholder="{{ __('Search part name or code') }}"><button>{{ __('Search') }}</button></form>
<div class="tech-parts">@forelse($parts as $part)<article class="tech-job"><small>{{ $part->sku }}</small><h2>{{ $part->name }}</h2><p>{{ $part->category }}</p><div class="tech-qty">{{ $part->quantity_on_hand }}</div><small>{{ __('Available') }}</small></article>@empty<p>{{ __('No available parts match your search.') }}</p>@endforelse</div>
<nav class="tech-pages">@if($parts->previousPageUrl())<a href="{{ $parts->previousPageUrl() }}">Previous</a>@endif<span>{{ $parts->currentPage() }} / {{ $parts->lastPage() }}</span>@if($parts->nextPageUrl())<a href="{{ $parts->nextPageUrl() }}">Next</a>@endif</nav>
@endsection
