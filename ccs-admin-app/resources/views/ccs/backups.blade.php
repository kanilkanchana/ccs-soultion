@extends('ccs.layout')
@section('title',__('Backup & Restore'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<style>.backup-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:20px 0}.backup-grid .card{margin:0}.backup-grid h2{font-size:19px}.backup-grid input[type=checkbox]{width:auto}.backup-grid button{margin-top:16px}.backup-grid p{line-height:1.7}.backup-grid label{margin-top:14px}.backup-grid .restore-box{border-color:#93734a}.zip-download-progress{margin-top:12px;min-width:230px}.zip-download-progress progress{width:100%;height:14px}.zip-download-progress p{margin:5px 0}.zip-download-error{color:#d64d40;white-space:pre-line;max-width:480px}@media(max-width:850px){.backup-grid{grid-template-columns:1fr}}</style>
<div class="directory-head"><div><h1>{{ __('Backup & Restore') }}</h1><p>{{ __('Owner access only · Database backups') }}</p></div><a class="btn secondary" href="{{ route('ccs.settings') }}">{{ __('System Settings') }}</a></div>
@if(!$supported)
<section class="card"><h2>{{ __('Backup Tool Unavailable') }}</h2><p>{{ __('This tool needs a file-based SQLite database and the PHP sqlite3 and zip extensions. MySQL backups are not supported here.') }}</p></section>
@else
<div class="backup-grid"><section class="card"><h2>{{ __('Download Database Backup') }}</h2><p>{{ __('Create a checked SQLite snapshot containing customers, jobs, invoices, payments, stock, warranties, claims and staff accounts.') }}</p><p class="muted">{{ __('The backup contains private customer and account data. Keep the ZIP file private. It does not include source code, uploaded files or your .env.') }}</p><form method="POST" action="{{ route('ccs.settings.backups.create') }}" data-zip-download data-fallback-name="CCS_Backup.zip">@csrf<button>{{ __('Create & Download Backup') }}</button><div class="zip-download-progress" hidden><progress max="100" aria-label="{{ __('ZIP download progress') }}"></progress><p role="status" aria-live="polite"></p></div><p class="zip-download-error" role="alert" hidden></p></form></section>
<section class="card restore-box"><h2>{{ __('Prepare Restore') }}</h2><p>{{ __('Upload a CCS backup ZIP from this installation. Validation and review do not change the live database.') }}</p><form id="backup-restore-upload" method="POST" enctype="multipart/form-data" action="{{ route('ccs.settings.backups.stage') }}">@csrf<label>{{ __('CCS Backup ZIP') }}<input name="backup_file" type="file" accept=".zip,application/zip" required></label><label>{{ __('Current Owner Password') }}<input name="password" type="password" required autocomplete="current-password"></label><label><input name="confirmation" type="checkbox" value="1" required> {{ __('I understand that the final restore replaces current database data with this backup.') }}</label><button>{{ __('Validate & Review Backup') }}</button><div id="backup-upload-progress" hidden style="margin-top:16px"><progress id="backup-upload-meter" max="100" value="0" style="width:100%;height:18px;accent-color:#9ee4bc" aria-label="Backup ZIP upload progress"></progress><p id="backup-upload-status" role="status" aria-live="polite" style="margin:6px 0"></p></div><p id="backup-upload-error" role="alert" hidden style="color:#ffb6aa;white-space:pre-line"></p></form><small>{{ __('Maximum database size: 300 MB. Upload limits in PHP may be lower.') }}</small><p class="muted">Web PHP: upload_max_filesize <strong>{{ ini_get('upload_max_filesize') }}</strong> · post_max_size <strong>{{ ini_get('post_max_size') }}</strong><br>PHP configuration: <code>{{ php_ini_loaded_file() ?: 'No php.ini loaded' }}</code><br>Upload temporary folder: <code>{{ ini_get('upload_tmp_dir') ?: sys_get_temp_dir() }}</code></p></section></div>
<section class="card"><div class="directory-head"><div><h2>{{ __('All Reports ZIP') }}</h2><p>{{ __('Download all-date CSV reports in a dated folder. Open them in Excel after extracting the ZIP.') }}</p></div><form method="POST" action="{{ route('ccs.settings.backups.reports') }}" data-zip-download data-fallback-name="CCS_Reports.zip">@csrf<button>{{ __('Download All Reports ZIP') }}</button><div class="zip-download-progress" hidden><progress max="100" aria-label="{{ __('ZIP download progress') }}"></progress><p role="status" aria-live="polite"></p></div><p class="zip-download-error" role="alert" hidden></p></form></div><p class="muted">{{ __('Sales, daily/monthly summaries, payments, outstanding dues, customers, vehicles, jobs, stock, serials, battery readings, warranties, claims and audit history.') }}</p>
@if(count($reports))
<details><summary>{{ __('Previous Reports Downloads') }}</summary>
@foreach($reports as $report)
<p><a href="{{ route('ccs.settings.backups.reports-download',$report['id']) }}">{{ $report['filename'] }}</a> · {{ $report['files'] }} CSV · {{ number_format($report['bytes']/1048576,2) }} MB</p>
@endforeach
</details>
@endif
</section>
<section class="directory-panel"><h2 class="directory-section-title">{{ __('Saved Backups') }}</h2><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Created') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Customers') }}</th><th>{{ __('Jobs') }}</th><th>{{ __('Invoices') }}</th><th>{{ __('Size') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($files as $backup)
<tr><td>{{ \Illuminate\Support\Carbon::parse($backup['created_at'])->timezone('Asia/Colombo')->format('Y-m-d H:i') }}<small>{{ $backup['id'] }}</small></td><td>{{ __($backup['reason']==='before_restore'?'Safety before restore':($backup['reason']==='before_business_reset'?'Safety before data reset':'Manual backup')) }}</td><td>{{ $backup['counts']['customers'] }}</td><td>{{ $backup['counts']['repair_jobs'] }}</td><td>{{ $backup['counts']['invoices'] }}</td><td>{{ number_format($backup['bytes']/1048576,2) }} MB</td><td><a class="btn secondary" href="{{ route('ccs.settings.backups.download',$backup['id']) }}">{{ __('Download') }}</a></td></tr>
@empty
<tr><td colspan="7" class="directory-empty">{{ __('No backups yet. Create your first backup above.') }}</td></tr>
@endforelse
</tbody></table></div><div class="directory-footer">{{ __('Latest 30 backups. Safety backups are kept automatically before restore.') }}</div></section>
@endif
<script>
(function(){
 if(!window.XMLHttpRequest||!window.FormData||!window.URL||!window.Blob)return;
 const t=window.ccsText||function(x){return x};
 document.querySelectorAll('form[data-zip-download]').forEach(function(form){
  let busy=false;const button=form.querySelector('button'),box=form.querySelector('.zip-download-progress'),bar=box.querySelector('progress'),status=box.querySelector('[role=status]'),error=form.querySelector('.zip-download-error');
  form.addEventListener('submit',function(e){e.preventDefault();if(busy)return;busy=true;button.disabled=true;form.setAttribute('aria-busy','true');error.hidden=true;error.textContent='';box.hidden=false;bar.removeAttribute('value');status.textContent=t('Preparing ZIP. Please wait…');
   const xhr=new XMLHttpRequest();xhr.open('POST',form.action,true);xhr.responseType='blob';xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
   function fail(message){busy=false;button.disabled=false;form.removeAttribute('aria-busy');box.hidden=true;error.textContent=message;error.hidden=false;}
   xhr.addEventListener('progress',function(e){if(e.lengthComputable&&e.total>0){const n=Math.min(100,Math.round(e.loaded/e.total*100));bar.value=n;status.textContent=t('Downloading ZIP…')+' '+n+'%';}else{bar.removeAttribute('value');status.textContent=t('Downloading ZIP…');}});
   xhr.addEventListener('error',()=>fail(t('Network error. Check your connection and try again.')));
   xhr.addEventListener('abort',()=>fail(t('Download cancelled.')));
   xhr.addEventListener('load',async function(){try{
    const type=(xhr.getResponseHeader('Content-Type')||'').toLowerCase(),blob=xhr.response;
    if(xhr.status<200||xhr.status>=300||!type.includes('application/zip')||!blob){let message='';if(blob&&type.includes('json')){try{const body=JSON.parse(await blob.text());message=body.errors?Object.values(body.errors).flat().join('\n'):(body.message||'');}catch(_){}}if(xhr.status===401||xhr.status===419)message=t('Your session expired. Sign in again and retry.');fail(message||t('ZIP was not downloaded. The server returned an error or a non-ZIP response.'));return;}
    const signature=new Uint8Array(await blob.slice(0,4).arrayBuffer());if(signature.length!==4||signature[0]!==80||signature[1]!==75||!((signature[2]===3&&signature[3]===4)||(signature[2]===5&&signature[3]===6)||(signature[2]===7&&signature[3]===8))){fail(t('ZIP was not downloaded because the response was not a valid ZIP file.'));return;}
    const declared=Number(xhr.getResponseHeader('Content-Length'));if(declared>0&&declared!==blob.size){fail(t('The ZIP download was incomplete. Please try again.'));return;}
    const cd=xhr.getResponseHeader('Content-Disposition')||'',m=cd.match(/filename="?([^";]+)"?/i);let name=m?m[1].trim():form.dataset.fallbackName;name=name.replace(/[\\/\x00-\x1f]/g,'_');if(!/\.zip$/i.test(name))name=form.dataset.fallbackName;
    const objectUrl=URL.createObjectURL(blob),a=document.createElement('a');a.href=objectUrl;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(objectUrl),60000);bar.value=100;status.textContent=t('ZIP ready. Check your browser downloads.');busy=false;button.disabled=false;form.removeAttribute('aria-busy');
   }catch(_){fail(t('ZIP could not be checked or downloaded. Please try again.'));}});
   xhr.send(new FormData(form));
  });
 });
})();
</script>
<script>
(function(){
 const form=document.getElementById('backup-restore-upload');
 if(!form||!window.XMLHttpRequest||!window.FormData)return;
 const box=document.getElementById('backup-upload-progress'),meter=document.getElementById('backup-upload-meter'),status=document.getElementById('backup-upload-status'),error=document.getElementById('backup-upload-error'),button=form.querySelector('button');
 const details=document.createElement('button');details.type='button';details.className='secondary';details.hidden=true;details.textContent=tFallback('Show validation details');form.appendChild(details);
 function tFallback(text){return window.ccsText?window.ccsText(text):text;}
 details.addEventListener('click',function(){if(!busy&&form.reportValidity())HTMLFormElement.prototype.submit.call(form);});
 const t=window.ccsText||function(text){return text;};let busy=false;
 form.addEventListener('submit',function(event){
  event.preventDefault();if(busy||!form.reportValidity())return;details.hidden=true;
  const data=new FormData(form),xhr=new XMLHttpRequest();busy=true;button.disabled=true;form.setAttribute('aria-busy','true');error.hidden=true;error.textContent='';box.hidden=false;meter.value=0;status.textContent=t('Uploading backup ZIP…')+' 0%';
  function fail(message){busy=false;button.disabled=false;form.removeAttribute('aria-busy');status.textContent=t('Upload / validation stopped.');error.textContent=message;error.hidden=false;form.elements.password.value='';}
  xhr.upload.addEventListener('progress',function(e){if(e.lengthComputable){const percent=Math.min(100,Math.round(e.loaded/e.total*100));meter.value=percent;status.textContent=percent===100?t('Upload complete. Validating backup…'):t('Uploading backup ZIP…')+' '+percent+'%';}else{meter.removeAttribute('value');status.textContent=t('Uploading backup ZIP…');}});
  xhr.upload.addEventListener('load',function(){meter.value=100;status.textContent=t('Upload complete. Validating backup…');});
  xhr.addEventListener('load',function(){
   let payload=null;try{payload=JSON.parse(xhr.responseText.trim().replace(/^\uFEFF/,''));}catch(e){}
   if(xhr.status>=200&&xhr.status<300&&payload&&payload.review_url){const url=new URL(payload.review_url,window.location.href);if(url.origin===window.location.origin){status.textContent=t('Backup validated. Opening review…');window.location.assign(url.href);return;}}
   if(xhr.status===422&&payload&&payload.errors){fail(Object.values(payload.errors).flat().join('\n'));return;}
   if(xhr.status===422&&payload&&typeof payload.message==='string'){fail(payload.message);return;}
   if(xhr.status===422){fail(t('The server rejected this request (HTTP 422), but its validation response could not be read. Re-enter your owner password and use Show validation details.'));details.hidden=false;return;}
   const messages={413:'The web server rejected the upload size. Check post_max_size, upload_max_filesize and the server request limit.',419:'Your session expired. Refresh this page, sign in if needed, and select the backup again.',401:'Please sign in again before uploading the backup.',403:'Only an authorized owner can prepare a restore.'};
   fail(t(messages[xhr.status]||'Backup upload or validation failed. Refresh the page and retry. If it repeats, check the server log.')+' (HTTP '+xhr.status+')');
  });
  xhr.addEventListener('error',function(){fail(t('Network error. Check your connection and retry the upload.'));});
  xhr.addEventListener('abort',function(){fail(t('Upload cancelled. Select the ZIP and retry.'));});
  xhr.open('POST',form.action);xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');xhr.send(data);
 });
})();
</script>
@endsection
