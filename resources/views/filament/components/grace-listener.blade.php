{{-- Grace Period Event Listener --}}
<div
    x-data
    x-on:grace-locked.window="
        alert('Grace Period: This feature is limited during grace period. Please update your billing to access.');
    "
    x-on:account-locked.window="
        alert('Account Locked: Your subscription has expired. Please renew immediately to restore access.');
    "
></div>
