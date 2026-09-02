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

    public int $refreshVersion = 0;

    public function mount(): void
    {
        $this->sync();
    }

    #[Computed]
    public function applications(): Collection
    {
        $this->refreshVersion;

        return Application::query()
            ->when($this->activeTab === 0, fn ($query) => $query
                ->where('status', 'queued')
                ->orderByRaw("case tier when 'A' then 1 when 'B' then 2 when 'C' then 3 else 4 end")
                ->orderBy('created_at'))
            ->when($this->activeTab === 1, fn ($query) => $query
                ->whereIn('status', ['applied', 'screening', 'interview', 'offer'])
                ->orderBy('next_action_due')
                ->orderByDesc('updated_at'))
            ->when($this->activeTab === 2, fn ($query) => $query
                ->whereIn('status', ['rejected', 'withdrawn', 'ghosted'])
                ->orderByDesc('updated_at'))
            ->get();
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
            $this->refreshVersion++;

            return;
        }

        $result = app(SyncApplications::class)->handle();
        $this->syncMessage = $result->message;
        $this->refreshVersion++;
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
