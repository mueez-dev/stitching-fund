<x-filament::modal
    id="subscription-modal"
    width="3xl"
    :close-by-clicking-away="$state !== 'locked'"
    :close-button="$state !== 'locked'"
>

    {{-- HEADER --}}
    <x-slot name="heading">
        @if($state === 'expiring')
             Subscription Expiring Soon
        @elseif($state === 'expired_grace')
             Subscription Expired (Grace Period)
        @elseif($state === 'locked')
             Account Locked
        @endif
    </x-slot>

    {{-- SUBHEADING (DATE + TIME) --}}
    <x-slot name="description">
        @if($state === 'expiring')
            Expires at
            <strong>
                {{ $expiresAt->format('d-m-Y H:i') }}
            </strong>
        @elseif($state === 'expired_grace')
            Grace period ends at
            <strong>
                {{ $graceEndsAt->format('d-m-Y H:i') }}
            </strong>
        @endif
    </x-slot>

    {{-- BODY --}}
    <div class="space-y-4 text-center">
        @if($state === 'expiring')
            <p class="text-gray-600">
                Your subscription will expire in
                <strong>{{ $daysLeft }} day(s)</strong>.
                Update now to avoid interruption.
            </p>

        @elseif($state === 'expired_grace')
            <p class="text-gray-600">
                Your subscription has expired.
                You are in grace period with
                <strong>{{ $daysLeft }} day(s)</strong> remaining.
            </p>

        @elseif($state === 'locked')
            <p class="text-red-600 font-medium">
                Your access is restricted.
                Only dashboard and billing are available.
            </p>
        @endif
    </div>

    {{-- FOOTER --}}
    <x-slot name="footer">
        <div class="flex justify-center gap-3 w-full">
            @if($state !== 'locked')
                <x-filament::button
                    color="gray"
                    wire:click="$dispatch('close-modal', { id: 'subscription-modal' })"
                >
                    Close
                </x-filament::button>
            @endif

            <x-filament::button
                color="primary"
                wire:click="renew"
            >
                {{ $state === 'expiring' ? 'Update Now' : 'Renew Now' }}
            </x-filament::button>
        </div>
    </x-slot>

</x-filament::modal>

{{-- SHOW EVERY 5 MINUTES FOR GRACE PERIOD --}}
@if($state === 'expired_grace')
<script>
    setInterval(() => {
        Livewire.dispatch('open-modal', { id: 'subscription-modal' });
    }, 300000); // 5 minutes = 300000ms
</script>
@endif
