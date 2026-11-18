@if(\Illuminate\Support\Facades\Route::has('ccs.website.index') && \Illuminate\Support\Facades\Schema::hasTable('ccs_website_requests') && in_array(app(\App\Services\CcsAdministration::class)->role(auth('web')->user()),['owner','manager','legacy','cashier'],true))
@php($websiteNew=\Illuminate\Support\Facades\DB::table('ccs_website_requests')->where('status','new')->count())
<section class="card no-print" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap"><div><strong>Website Requests / වෙබ් ඉල්ලීම්</strong><p>{{ $websiteNew }} new requests · bookings, quotations, warranty questions and customer concerns</p></div><a class="btn secondary" href="{{ route('ccs.website.index') }}">Review requests</a></section>
@endif
