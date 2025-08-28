@component('mail::message')
# New Contact Form Submission

You have received a new message from the contact form.

**Name:** {{ $contactData['name'] }}
**Email:** {{ $contactData['email'] }}

**Message:**
{{ $contactData['message'] }}

@component('mail::button', ['url' => 'mailto:' . $contactData['email']])
Reply to {{ $contactData['name'] }}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
