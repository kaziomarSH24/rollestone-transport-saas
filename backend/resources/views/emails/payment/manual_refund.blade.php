<x-mail::message>
# Cash Refund Processed

Hello **{{ $user->name }}**,

A cash refund has been successfully processed for your account.

<x-mail::panel>
**Refunded Amount:** {{ $amount }}<br>
**Processed By:** {{ $staff_name }}<br>
**Date:** {{ $refund_date }}
</x-mail::panel>

Your wallet balance has been updated accordingly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
