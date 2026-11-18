@extends('ccs.layout')
@section('title',__('Technician Workspace'))
@section('body-class','ccs-technician-page')
@section('content')
@include('ccs.technician.styles')
<div class="tech-heading"><div><h1>{{ __('Technician Workspace') }}</h1><p>{{ __('Current repairs, assignments and technical notes') }}</p></div><a class="btn secondary" href="{{ route('ccs.stock') }}">{{ __('Available Parts') }}</a></div>
<nav class="tech-tabs"><a href="{{ route('ccs.jobs',['scope'=>'my']) }}" @if($scope==='my') aria-current="page" @endif>{{ __('My Jobs') }}</a><a href="{{ route('ccs.jobs',['scope'=>'current']) }}" @if($scope==='current') aria-current="page" @endif>{{ __('Current Jobs') }}</a></nav>
<form class="tech-search" method="GET" action="{{ route('ccs.jobs') }}"><input type="hidden" name="scope" value="{{ $scope }}"><input name="q" maxlength="150" value="{{ $search }}" placeholder="{{ __('Search job, customer or vehicle') }}"><button>{{ __('Search') }}</button></form>
<div class="tech-workspace"><div class="tech-list">
@forelse($jobs as $job)
<a class="tech-job" href="{{ route('ccs.jobs',['scope'=>$scope,'q'=>$search,'job_id'=>$job->id,'page'=>$jobs->currentPage()]) }}"><small>{{ $job->job_number }} · {{ $job->registration_number }}</small><h2>{{ $job->make }} {{ $job->model }}</h2><span class="tech-status">{{ ucwords(str_replace('_',' ',$job->status)) }}</span><p>{{ $job->service_type }}</p><p>{{ $job->customer_name }}</p><small>{{ __('Technician') }}: {{ $job->technician_name ?: __('Unassigned') }}</small></a>
@empty<p>{{ __('No matching current jobs. Choose Current Jobs to see workshop work.') }}</p>@endforelse
</div><section class="tech-detail">
@if($selected)
<h2>{{ $selected->job_number }}</h2><p>{{ $selected->registration_number }} · {{ $selected->make }} {{ $selected->model }}</p><p><strong>{{ __('Customer') }}:</strong> {{ $selected->customer_name }}</p><h3>{{ __('Complaint / Symptoms') }}</h3><p>{{ $selected->complaint }}</p>
<form method="POST" action="{{ route('ccs.jobs.status',$selected->id) }}">@csrf
<label>{{ __('Repair Status') }}<select name="status" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected($selected->status===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></label>
<label>{{ __('Assign Technician') }}<select name="technician_id"><option value="">{{ __('Unassigned') }}</option>@foreach($technicians as $tech)<option value="{{ $tech->id }}" @selected((int)$selected->assigned_user_id===(int)$tech->id)>{{ $tech->name }}</option>@endforeach</select></label>
<label>{{ __('Diagnosis / Repair Notes') }}<textarea name="diagnosis" maxlength="5000">{{ old('diagnosis',$selected->diagnosis) }}</textarea></label>
<label>{{ __('Parts Notes') }}<textarea name="parts_notes" maxlength="3000">{{ old('parts_notes',$selected->parts_notes) }}</textarea></label>
<button>{{ __('Save Status, Assignment & Notes') }}</button></form>
@else<p>{{ __('Select a job to view details.') }}</p>@endif
</section></div>
<nav class="tech-pages">@if($jobs->previousPageUrl())<a href="{{ $jobs->previousPageUrl() }}">Previous</a>@endif<span>{{ $jobs->currentPage() }} / {{ $jobs->lastPage() }}</span>@if($jobs->nextPageUrl())<a href="{{ $jobs->nextPageUrl() }}">Next</a>@endif</nav>
@endsection
