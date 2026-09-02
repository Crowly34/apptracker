<?php

namespace App\NativeComponents;

use App\Models\Application;
use App\Services\SyncApplications;
use Illuminate\Database\Eloquent\Collection;
use Native\Mobile\Attributes\Computed;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Network;

class ApplicationsList extends NativeComponent
{
    public int $activeTab = 0;

    public ?string $syncMessage = null;

    public bool $isOffline = false;

    public function mount(): void
    {
        $this->sync();
    }

    #[Computed]
    public function applications(): Collection
    {
        return match ($this->activeTab) {
            1 => Application::active()->get(),
            2 => Application::closed()->get(),
            default => Application::queued()->get(),
        };
    }

    public function selectTab(int $tab): void
    {
        $this->activeTab = $tab;
    }

    public function sync(): void
    {
        $status = Network::status();
        $this->isOffline = $status !== null && ! $status->connected;

        if ($this->isOffline) {
            $this->syncMessage = 'Offline — showing your last sync.';

            return;
        }

        $result = app(SyncApplications::class)->handle();
        $this->syncMessage = $result->message;
    }

    public function openApplication(int $id): void
    {
        $this->navigate("/applications/{$id}");
    }

    public function render(): Element
    {
        return $this->view('applications-list');
    }
}
