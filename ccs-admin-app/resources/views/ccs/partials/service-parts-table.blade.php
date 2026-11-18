@if(count($documentTableRows))
<div class="battery-table-wrap"><table class="battery-table"><thead><tr><th>{{ __('No.') }}</th><th>{{ __('Part Name') }}</th><th>{{ __('Serial Number') }}</th><th>{{ __('Reading / Details') }}</th></tr></thead><tbody>
@foreach($documentTableRows as $documentRow)
<tr><td>{{ $documentOffset+$loop->iteration }}</td><td>{{ $documentRow['name'] }}@if($documentRow['position']!==null)<small style="display:block">{{ __('Module Position') }} {{ $documentRow['position'] }}</small>@endif @if($documentRow['draft'])<small style="display:block">{{ __('Draft / Reserved') }}</small>@endif</td><td>{{ $documentRow['serial'] ?: '—' }}</td><td>{{ $documentRow['details'] }}@if($documentRow['notes']!=='')<small style="display:block">{{ $documentRow['notes'] }}</small>@endif</td></tr>
@endforeach
</tbody></table></div>
@elseif($documentOffset===0)
<p class="battery-muted">{{ __('No saved parts or serial readings for this job yet.') }}</p>
@endif
