<native:top-bar title="Settings" />

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full p-4 gap-5">
        <native:text class="text-base text-theme-on-surface-variant">This build syncs read-only from the backend. Connection details are baked in at build time.</native:text>

        <native:column class="gap-1">
            <native:text class="text-sm font-semibold text-theme-on-surface-variant">Sync host</native:text>
            <native:text class="text-base text-theme-on-background">{{ $host !== '' ? $host : 'Not configured' }}</native:text>
        </native:column>

        <native:divider class="border-theme-outline-variant" />

        <native:column class="gap-1">
            <native:text class="text-sm font-semibold text-theme-on-surface-variant">Bearer token</native:text>
            <native:text class="text-base text-theme-on-background">{{ $tokenPreview }}</native:text>
        </native:column>

        @if ($lastSyncedAt !== null)
            <native:divider class="border-theme-outline-variant" />

            <native:column class="gap-1">
                <native:text class="text-sm font-semibold text-theme-on-surface-variant">Last synced</native:text>
                <native:text class="text-base text-theme-on-background">{{ $lastSyncedAt }}</native:text>
            </native:column>
        @endif

        <native:button label="Test connection" variant="primary" icon="sync" @tap="testConnection" class="w-full" />

        @if ($message !== null)
            <native:text class="text-sm text-theme-on-surface-variant">{{ $message }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
