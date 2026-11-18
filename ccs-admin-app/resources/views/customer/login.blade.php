@extends('customer.layout')
@section('title','Customer Login')
@section('content')
<section class="portal-card" style="max-width:450px;margin:30px auto"><h1>Customer Login / ඇතුළු වන්න</h1><p class="portal-muted">Use the login details provided by CCS Solutions.<br>CCS Solutions ලබාදුන් පිවිසුම් විස්තර භාවිත කරන්න.</p>
<form method="POST" action="{{ route('customer.login.submit') }}">@csrf
<label for="username">Username / පරිශීලක නම</label><input id="username" name="username" required maxlength="80" autocomplete="username" value="{{ old('username') }}" autofocus>
<label for="password">Password / මුරපදය</label><input id="password" name="password" type="password" required maxlength="200" autocomplete="current-password">
<button type="submit" class="portal-form-actions">Sign In / ඇතුළු වන්න</button>
</form><p class="portal-muted">Need access or forgot your password? Contact the workshop.<br>පිවිසුමක් හෝ අලුත් මුරපදයක් සඳහා වැඩපොළ අමතන්න.</p></section>
@endsection
