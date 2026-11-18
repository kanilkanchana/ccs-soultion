<div class="serial-entry" data-serial-entry @if($serialEntryRequired ?? false) data-required="1" @endif>
<div class="serial-toolbar"><strong>{{ __('Actual Serial Numbers') }}</strong><label>{{ __('Quantity') }} <input data-serial-count type="number" value="0" readonly aria-label="{{ __('Quantity') }}"></label><button type="button" class="secondary" data-serial-add>+ {{ __('Add Serial') }}</button></div>
<div data-serial-lines></div><textarea name="serials" data-serial-canonical hidden>{{ $serialEntryValue ?? '' }}</textarea>
<small>{{ __('Each serial is one unit. Quantity updates automatically. Edit a row or remove it before saving.') }}</small>
</div>
