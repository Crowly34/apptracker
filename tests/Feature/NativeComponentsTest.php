<?php

namespace Tests\Feature;

use App\Models\Application;
use App\NativeComponents\ApplicationDetail;
use App\NativeComponents\ApplicationsList;
use App\NativeComponents\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;
use Tests\TestCase;

class NativeComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_renders_the_active_tab_and_switches_tabs(): void
    {
        Application::factory()->queued()->create(['company' => 'Queued Co', 'tier' => 'A']);
        Application::factory()->create(['company' => 'Interviewing Co', 'status' => 'interview']);

        Native::test(ApplicationsList::class)
            ->assertSee('Queued Co')
            ->assertDontSee('Interviewing Co')
            ->call('selectTab', 1)
            ->assertSet('activeTab', 1)
            ->assertSee('Interviewing Co')
            ->assertDontSee('Queued Co')
            ->assertAccessible();
    }

    public function test_the_detail_screen_shares_a_posting_url(): void
    {
        $application = Application::factory()->queued()->create([
            'company' => 'NativePHP',
            'role' => 'Mobile Engineer',
            'posting_url' => 'https://nativephp.com/jobs/42',
        ]);

        Native::test(ApplicationDetail::class, params: ['id' => $application->id])
            ->tap('Share posting')
            ->assertNativeCalled('Share.Url', fn (array $params): bool => ($params['url'] ?? null) === 'https://nativephp.com/jobs/42');
    }

    public function test_the_settings_screen_shows_the_baked_in_target(): void
    {
        config([
            'apptracker.mobile.host' => 'http://apptracker.example.test',
            'apptracker.mobile.token' => 'abcdefghijkl',
        ]);

        Native::test(Settings::class)
            ->assertSee('apptracker.example.test')
            ->assertSee('••••••••ijkl')
            ->assertDontSee('abcdefghijkl')
            ->assertAccessible();
    }
}
