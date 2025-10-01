@component('mail::message')
# Forgot {{ config('app.name') }} Password
<br>
<h2>Greetings <strong>{{ $user->username }}</strong>,</h2>
<br>
<p>To reset the password of your {{ config('app.name') }} account, please click the button provided below.</p>

@component('mail::button', ['url' => $tokenURL, 'color' => 'success'])
	<strong>Reset Password</strong>
@endcomponent

<p>If you haven't made this request at all, please disregard this message.</p>

Many Thanks,<br>
<strong>The {{ config('app.name') }} Team</strong>
@endcomponent
