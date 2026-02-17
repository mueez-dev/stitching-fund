{{-- Grace Period Event Listener --}}
<div
    x-data
    x-on:grace-locked.window="
        // Simple alert notification
        alert('Feature Locked: This feature is not available during grace period. Please update your billing to access.');
    "
></div>
