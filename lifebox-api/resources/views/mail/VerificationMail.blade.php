@component('mail::message')
# Verify {{ config('app.name') }} Account
<br>
<h2>Greetings <strong>{{ $name }}</strong>,</h2>
<br>
<p>To verify your email address as well as your new {{ config('app.name') }} account, please click the button provided below.</p>

@component('mail::button', ['url' => $verificationURL, 'color' => 'success'])
	<strong>Verify My {{ config('app.name') }} Account</strong>
@endcomponent

@component('mail::panel')
	<h2><strong>IMPORTANT NOTE</strong></h2> <br>
	<p>After successfully verifying your account, you can now login to {{ config('app.name') }} (both on web and mobile application) by typing in your <strong>Lifebox email address</strong>, then followed by your Lifebox password.</p> <br>
	<p>Your Lifebox email address is shown below to guide you through your Lifebox login:</p> <br>
	<p>Lifebox Email Address: <strong>{{ $lifeboxEmail }}</strong></p> <br>
@endcomponent

<p>If you did not create a {{ config('app.name') }} account at all, no further action is required.</p>

Many Thanks,<br>
<strong>The {{ config('app.name') }} Team</strong>
@endcomponent
