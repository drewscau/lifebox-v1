@component('mail::message')
# Resubscribe To {{ config('app.name') }}
<br>
<h2>Greetings {{ $name }},</h2>
<br>
<p>Please resubscribe to {{ config('app.name') }} to access and manage your files. Click the button below to redirect you to the {{ config('app.name') }} Subscription page.</p>

@component('mail::button', ['url' => $subcribe_link, 'color' => 'success'])
	<strong>Subscribe My Account</strong>
@endcomponent

<p>For us at {{ config('app.name') }}, it would be such a sweet sorrow to see you go. If you wish to terminate your account, please proceed by clicking the Terminate button below.</p>

@component('mail::button', ['url' => $terminate_link, 'color' => 'error'])
	<strong>Terminate My Account</strong>
@endcomponent

<p>If you are already unsubscribed from {{ config('app.name') }}, you have the option to download all your {{ config('app.name') }} files (if there are any) by clicking the button below. It will download all your files compressed in a .ZIP file afterwards.</p>

@component('mail::button', ['url' => $download_link, 'color' => 'primary'])
	<strong>Download All My Files</strong>
@endcomponent

Many Thanks,<br>
<strong>The {{ config('app.name') }} Team</strong>
@endcomponent