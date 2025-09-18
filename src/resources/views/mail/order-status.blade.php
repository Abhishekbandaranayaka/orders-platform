@component('mail::message')
# Order Update

**Order ID:** {{ $orderId }}  
**Customer ID:** {{ $customerId }}  
**Status:** {{ ucfirst($status) }}  
**Payment:** {{ ucfirst($paymentStatus) }}  
**Total:** {{ number_format($totalCents/100, 2) }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
