@extends('ccs.layout')
@section('title','Website Content')
@section('content')
@php
$kinds=\App\Services\CcsWebsiteContent::KINDS;$data=$editing?->data??[];
$value=fn($key,$default='')=>old($key,$data[$key]??$default);
@endphp
<style>
.cms-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.cms-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:16px 0}.cms-tabs a{padding:12px 16px;border:1px solid var(--t-border,#29414e);border-radius:9px}.cms-tabs a[aria-current=page]{background:var(--t-button,#ffd129);color:var(--t-button-ink,#111)}.cms-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr));gap:16px}.cms-card{padding:0!important;overflow:hidden;margin:0!important;min-width:0}.cms-photo{width:100%;height:190px;object-fit:contain;background:#071117}.cms-card-body{padding:16px}.cms-card h2{font-size:17px;margin:8px 0;overflow-wrap:anywhere}.cms-status{padding:4px 9px;border-radius:20px;background:#164b3d;color:#b7ffe4;font-size:12px}.cms-status.off{background:#523b28;color:#ffe3bc}.cms-card .actions{margin-top:16px;gap:8px}.cms-card p{overflow-wrap:anywhere}.cms-editor{margin:16px 0}.cms-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.cms-fields .wide{grid-column:1/-1}.cms-editor input[type=checkbox]{width:auto}.cms-editor textarea{min-height:95px}.cms-guide{font-size:12px;color:var(--t-muted,#a9becd)}.cms-filter{margin:12px 0}.cms-filter input{max-width:460px}.cms-card small{display:block}.cms-card[hidden]{display:none!important}@media(max-width:650px){.cms-fields{grid-template-columns:1fr}.cms-photo{height:170px}.cms-head .btn{width:100%;text-align:center}}
</style>
<div class="cms-head"><div><h1>Website Content</h1><p class="muted">Ads, car models, videos and offers shown on the public website.</p></div><a class="btn" href="{{ route('ccs.website.content',['kind'=>$kind,'new'=>1]) }}#content-form">+ Add {{ $kinds[$kind] }}</a></div>
<nav class="cms-tabs" aria-label="Website content categories">@foreach($kinds as $key=>$label)<a href="{{ route('ccs.website.content',['kind'=>$key]) }}" @if($key===$kind) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
@if($kind==='videos')@include('ccs.partials.facebook-sync')@endif
<details class="card cms-editor" id="content-form" @if($editing||request()->boolean('new')||$errors->any()) open @endif><summary>{{ $editing?'Edit card':'Add a new card' }}</summary>
<form method="POST" enctype="multipart/form-data" action="{{ route('ccs.website.content.save') }}">@csrf
<input type="hidden" name="kind" value="{{ $kind }}">@if($editing)<input type="hidden" name="id" value="{{ $editing->id }}"><input type="hidden" name="revision" value="{{ $editing->revision }}">@endif
<div class="cms-fields">
<div><label for="cms-title">Title / card name *</label><input id="cms-title" name="title" required maxlength="180" value="{{ old('title',$editing?->title??'') }}"></div>
<div><label for="cms-order">Display order</label><input id="cms-order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order',$editing?->sort_order??0) }}"></div>
<div class="wide"><label><input name="active" type="hidden" value="0"><input name="active" type="checkbox" value="1" @checked(old('active',$editing?->active??true))> Active — visible on the website</label></div>
@if($kind==='models')
@foreach(['make'=>'Make / brand','model'=>'Model name','code'=>'Model / chassis code'] as $field=>$label)<div><label for="cms-{{ $field }}">{{ $label }} *</label><input id="cms-{{ $field }}" name="{{ $field }}" required maxlength="120" value="{{ $value($field) }}"></div>@endforeach
<div><label for="cms-type">Vehicle type</label><select id="cms-type" name="type">@foreach(['EV', 'HEV', 'HEV / PHEV', 'HEV/PHEV', 'ICE', 'IMA', 'IMA / HEV', 'IMA / i-DCD / HEV', 'MHEV', 'MHEV/HEV', 'PHEV', 'SERIES HEV', 'Series hybrid', 'e:HEV', 'i-DCD', 'i-DCD / e:HEV'] as $type)<option @selected($value('type','HEV')===$type)>{{ $type }}</option>@endforeach</select></div>
@endif
@if($kind==='videos')
<div><label for="cms-platform">Video source *</label><select id="cms-platform" name="platform">@foreach(['video'=>'Direct video / upload','facebook'=>'Facebook','instagram'=>'Instagram','tiktok'=>'TikTok','youtube'=>'YouTube'] as $code=>$label)<option value="{{ $code }}" @selected(strtolower($value('platform','video'))===$code)>{{ $label }}</option>@endforeach</select></div>
<div><label for="cms-url">Original video URL</label><input id="cms-url" name="url" type="url" maxlength="1500" value="{{ $value('url') }}" placeholder="https://..."><small class="cms-guide">Paste the original public video/reel link. Embed HTML is not needed.</small></div>
<div class="wide"><label for="cms-video">Or upload MP4 / WebM (Direct video only)</label><input id="cms-video" name="video" type="file" accept="video/mp4,video/webm"><small class="cms-guide">Up to 100 MB, subject to your hosting upload limit. Use an HTTPS video URL for larger files.</small></div>
@endif
<div><label for="cms-image">{{ $kind==='videos'?'Thumbnail':'Image' }} upload</label><input id="cms-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/avif"><small class="cms-guide">JPG, PNG, WebP, AVIF; up to 10 MB. Existing file is retained when left empty.</small></div>
<div><label for="cms-image-url">Or HTTPS image URL</label><input id="cms-image-url" name="image_url" type="url" maxlength="1500" value="{{ old('image_url',$data['image']??$data['asset']??'') }}"></div>
<div class="wide"><label for="cms-description">Description</label><textarea id="cms-description" name="description" maxlength="4000">{{ $value('description') }}</textarea></div>
@if(in_array($kind,['ads','offers']))
@foreach(['subtitle'=>'Highlighted second title line','kicker'=>'Small heading / category','service'=>'Service for enquiry','cta_label'=>'Button text','cta_url'=>'Button link (optional)','discount_label'=>'Discount label','terms'=>'Terms / note'] as $field=>$label)<div><label for="cms-{{ $field }}">{{ $label }}</label><input id="cms-{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}"></div>@endforeach
<div><label for="cms-discount">Discount % (optional)</label><input id="cms-discount" name="discount" type="number" step="0.01" min="0" max="100" value="{{ $value('discount') }}"></div>
@endif
@if($kind==='offers')<div class="wide"><label for="cms-bullets">Details — one per line</label><textarea id="cms-bullets" name="bullets">{{ old('bullets',implode("\n",$data['bullets']??[])) }}</textarea></div>@endif
<div class="wide"><label for="cms-alt">Image description / alt text</label><input id="cms-alt" name="alt" maxlength="250" value="{{ $value('alt') }}"></div>
</div><div class="actions" style="margin-top:16px"><button>Save {{ $editing?'changes':'card' }}</button><a class="btn secondary" href="{{ route('ccs.website.content',['kind'=>$kind]) }}">Cancel</a></div>
</form></details>
<div class="cms-filter"><label for="cms-search">Search {{ $kinds[$kind] }}</label><input id="cms-search" type="search" placeholder="Search title, make or code…"></div>
<p class="muted">{{ $rows->count() }} cards · {{ $rows->where('active',true)->count() }} active. Disabled cards stay here for later editing.</p>
<div class="cms-grid" id="cms-cards">@forelse($rows as $row)
<article class="card cms-card" data-search="{{ mb_strtolower($row->title.' '.($row->data['make']??'').' '.($row->data['code']??'')) }}">
@if(!empty($row->data['photo'])&&empty($row->data['image'])&&empty($row->data['asset']))<img class="cms-photo" src="{{ rtrim(config('ccs_website.public_site_url') ?: preg_replace('~^(https?://)admin\\.~','$1',config('app.url')), '/') }}/assets/vehicles/{{ rawurlencode($row->data['photo']) }}" alt="{{ $row->title }}" loading="lazy">@elseif(!empty($row->data['image'])||!empty($row->data['asset']))<img class="cms-photo" src="{{ $row->data['image']??$row->data['asset'] }}" alt="{{ $row->data['alt']??$row->title }}" loading="lazy">@else<div class="cms-photo" style="display:grid;place-items:center">{{ $kind==='models'?'Photo unavailable':'▶ Video' }}</div>@endif
<div class="cms-card-body"><span class="cms-status {{ $row->active?'':'off' }}">{{ $row->active?'Active':'Disabled' }}</span><h2>{{ $row->title }}</h2><small class="muted">Order {{ $row->sort_order }} · {{ $row->data['make']??$row->data['platform']??$row->data['kicker']??'' }}</small><p>{{ \Illuminate\Support\Str::limit($row->data['description']??$row->data['code']??'',120) }}</p><div class="actions"><a class="btn secondary" href="{{ route('ccs.website.content',['kind'=>$kind,'edit'=>$row->id]) }}#content-form">Edit</a><form method="POST" action="{{ route('ccs.website.content.status',$row->id) }}">@csrf<input type="hidden" name="active" value="{{ $row->active?0:1 }}"><input type="hidden" name="revision" value="{{ $row->revision }}"><button class="secondary">{{ $row->active?'Disable':'Activate' }}</button></form></div></div></article>
@empty<p>No cards yet. Add your first {{ strtolower($kinds[$kind]) }} card above.</p>@endforelse</div>
<script>document.getElementById('cms-search').addEventListener('input',e=>{const q=e.target.value.toLocaleLowerCase().trim();for(const card of document.querySelectorAll('#cms-cards [data-search]'))card.hidden=!card.dataset.search.includes(q);});</script>
@endsection
