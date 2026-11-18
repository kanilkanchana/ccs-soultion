@extends('ccs.layout')
@section('title',__('Print Stock Labels'))
@section('content')
<section class="card"><h1>{{ __('Print Stock Labels') }}</h1><h2>{{ $part->name }}</h2><p>{{ $part->sku }}</p><form method="GET" action="{{ route('ccs.stock.labels.print',$part->id) }}" target="_blank">
@if($part->has_serial)
<label>{{ __('Actual Serial Number') }}<select name="unit_id" required><option value="">{{ __('Select an available serial.') }}</option>
@foreach($units as $unit)
<option value="{{ $unit->id }}">{{ $unit->serial_number }}</option>
@endforeach
</select></label>
@else
<label>{{ __('Product Barcode / SKU') }}<select name="code_source"><option value="sku">SKU · {{ $part->sku }}</option>@if($part->barcode)<option value="barcode">Barcode · {{ $part->barcode }}</option>@endif</select></label>
@endif
<label>{{ __('Copies') }}<input name="copies" type="number" min="1" max="50" required value="1"></label><button>{{ __('Preview & Print Labels') }}</button></form><p class="muted">Code128 · {{ $preferences['label_width'] }} × {{ $preferences['label_height'] }} mm. {{ __('Use the browser print dialog to choose your printer and actual paper size. Print at 100% scale.') }}</p><a href="{{ route('ccs.settings') }}">{{ __('Scanner & Printing Settings') }}</a></section>
@endsection
