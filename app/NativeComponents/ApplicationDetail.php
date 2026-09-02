<?php

namespace App\NativeComponents;

use App\Models\Application;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;
use Native\Mobile\Facades\Share;

class ApplicationDetail extends NativeComponent
{
    /** @var array<string, mixed> */
    public array $application = [];

    public ?string $actionMessage = null;

    public function mount(): void
    {
        $this->application = Application::query()->findOrFail($this->param('id'))->toArray();
    }

    public function openPosting(): void
    {
        $url = $this->application['posting_url'] ?? null;

        if (! is_string($url) || ! Browser::inApp($url)) {
            $this->actionMessage = 'This posting could not be opened.';
        }
    }

    public function sharePosting(): void
    {
        $url = $this->application['posting_url'] ?? null;

        if (! is_string($url)) {
            return;
        }

        Share::url(
            'Job posting',
            "{$this->application['company']} — {$this->application['role']}",
            $url,
        );
    }

    public function render(): Element
    {
        return $this->view('application-detail');
    }
}
