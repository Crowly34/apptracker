@use('App\Enums\StatusEnum')
@php($status = StatusEnum::from($application['status']))

<native:top-bar title="{{ $application['company'] }}" />

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full p-4 gap-5">
        <native:column class="gap-2">
            <native:text class="text-2xl font-bold text-theme-on-background">{{ $application['role'] }}</native:text>
            <native:row class="gap-2 items-center">
                <native:badge label="{{ ucfirst($status->value) }}" variant="{{ $status->badgeVariant() }}" />
                @if ($application['tier'] !== null)
                    <native:badge label="Tier {{ $application['tier'] }}" variant="primary" />
                @endif
            </native:row>
        </native:column>

        @if ($application['next_action'] !== null)
            <native:column class="w-full p-4 gap-1 rounded-lg bg-theme-surface-variant">
                <native:text class="text-sm font-semibold text-theme-on-surface-variant">Next action</native:text>
                <native:text class="text-base text-theme-on-surface">{{ $application['next_action'] }}</native:text>
                @if ($application['next_action_due'] !== null)
                    <native:text class="text-sm text-theme-on-surface-variant">Due {{ $application['next_action_due'] }}</native:text>
                @endif
            </native:column>
        @endif

        <native:column class="gap-3">
            @foreach (['source' => 'Source', 'applied_at' => 'Applied', 'notes' => 'Notes'] as $field => $heading)
                @if ($application[$field] !== null)
                    <native:column class="gap-1">
                        <native:text class="text-sm font-semibold text-theme-on-surface-variant">{{ $heading }}</native:text>
                        <native:text class="text-base text-theme-on-background">{{ $application[$field] }}</native:text>
                    </native:column>
                @endif
            @endforeach
        </native:column>

        @if ($application['posting_url'] !== null)
            <native:column class="gap-3">
                <native:button label="Open posting" variant="primary" icon="link" @tap="openPosting" class="w-full" />
                <native:button label="Share posting" variant="secondary" icon="share" @tap="sharePosting" class="w-full" />
            </native:column>
        @endif

        @if ($actionMessage !== null)
            <native:text class="text-sm text-theme-destructive">{{ $actionMessage }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
