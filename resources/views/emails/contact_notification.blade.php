@component('mail::message')
# New Contact Message

You have received a new inquiry from the website.

**Details:**
- **Name:** {{ $contactMessage->full_name }}
- **Email:** {{ $contactMessage->email }}
- **Phone:** {{ $contactMessage->phone_number }}
- **Category:** {{ $contactMessage->category }}

**Subject:** {{ $contactMessage->subject }}

**Message:**
{{ $contactMessage->message }}

@component('mail::button', ['url' => config('app.url')])
Go to Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent