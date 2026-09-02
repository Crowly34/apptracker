<?php

namespace App\NativeComponents;

use App\Models\MobileSetting;
use App\Services\SyncApplications;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

class Settings extends NativeComponent
{
    public string $host = '';

    public string $tokenPreview = '';

    public ?string $lastSyncedAt = null;

    public ?string $message = null;

    public function mount(): void
    {
        $this->host = (string) config('apptracker.mobile.host');
        $this->tokenPreview = $this->maskToken((string) config('apptracker.mobile.token'));
        $this->lastSyncedAt = MobileSetting::singleton()->last_synced_at?->diffForHumans();
    }

    public function testConnection(): void
    {
        $result = app(SyncApplications::class)->handle();
        $this->message = $result->message;
        $this->lastSyncedAt = MobileSetting::singleton()->last_synced_at?->diffForHumans();
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return 'Not configured';
        }

        return str_repeat('•', 8).substr($token, -4);
    }

    public function render(): Element
    {
        return $this->view('settings');
    }
}
