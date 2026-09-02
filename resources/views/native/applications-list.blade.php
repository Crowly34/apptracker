@php
    $statusVariant = fn (string $status): string => match ($status) {
        'queued' => 'accent',
        'rejected', 'withdrawn', 'ghosted' => 'destructive',
        default => 'primary',
    };
@endphp

<native:top-bar title="AppTracker" display-mode="large">
    <native:top-bar-action id="settings" label="Settings" icon="settings" url="/settings" a11y-label="Settings" />
</native:top-bar>

<native:column class="w-full h-full bg-theme-background">
    <native:tab-row :selected-index="$activeTab" @change="selectTab" class="w-full px-3 py-2">
        <native:tab label="Queue" />
        <native:tab label="Active" />
        <native:tab label="Closed" />
    </native:tab-row>

    @if ($syncMessage !== null)
        <native:row class="w-full gap-2 px-4 py-2 items-center bg-theme-surface-variant">
            <native:icon name="{{ $isOffline ? 'wifi-off' : 'sync' }}" :size="16" class="text-theme-on-surface-variant" a11y-label="Sync status" />
            <native:text class="text-sm text-theme-on-surface-variant">{{ $syncMessage }}</native:text>
        </native:row>
    @endif

    <native:refreshable @refresh="sync" class="w-full flex-1">
        @forelse ($this->applications as $application)
            <native:pressable @tap="openApplication({{ $application->id }})" class="w-full px-4 py-4 gap-2">
                <native:row class="w-full gap-3 items-start">
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-lg font-semibold text-theme-on-background">{{ $application->company }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $application->role }}</native:text>
                    </native:column>
                    <native:column class="gap-1 items-end">
                        <native:badge label="{{ ucfirst($application->status->value) }}" variant="{{ $statusVariant($application->status->value) }}" />
                        @if ($application->tier !== null)
                            <native:badge label="Tier {{ $application->tier->value }}" variant="primary" />
                        @endif
                    </native:column>
                </native:row>
            </native:pressable>
            <native:divider class="border-theme-outline-variant" />
        @empty
            <native:column class="w-full p-8 gap-2 items-center">
                <native:icon name="inbox" :size="32" class="text-theme-on-surface-variant" a11y-label="Empty" />
                <native:text class="text-base text-theme-on-surface-variant">No applications in this view.</native:text>
            </native:column>
        @endforelse
    </native:refreshable>
</native:column>
