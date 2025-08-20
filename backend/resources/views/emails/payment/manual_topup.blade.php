<x-mail::message>
# Cash Top-up Successful

Hello **{{ $user->name }}**,

A cash top-up has been successfully added to your account.

<x-mail::panel>
**Top-up Amount:** ${{ $amount }}<br>
**Processed By:** {{ $staff_name }}<br>
**Date:** {{ $topup_date }}
</x-mail::panel>

Your new wallet balance has been updated.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
