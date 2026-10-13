<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TawkWidgetTest extends TestCase
{
    use RefreshDatabase;

    private const PROPERTY = '6acdeeebaa4df134c2d1cb87';
    private const WIDGET = '1k4q6qie2';

    private function configure(): void
    {
        config([
            'tawk.property_id' => self::PROPERTY,
            'tawk.widget_key' => self::WIDGET,
        ]);
    }

    private function embedUrl(): string
    {
        return 'https://embed.tawk.to/' . self::PROPERTY . '/' . self::WIDGET;
    }

    public function test_storefront_renders_tawk_widget_when_configured(): void
    {
        $this->configure();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('embed.tawk.to', false)
            ->assertSee($this->embedUrl(), false)
            ->assertSee('Tawk_API', false);
    }

    public function test_user_profile_page_renders_tawk_widget(): void
    {
        $this->configure();

        $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

        // Halaman profile (layout app) ikut memuat widget.
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('embed.tawk.to', false)
            ->assertSee($this->embedUrl(), false);

        // Halaman akun customer (layout front) juga.
        $this->actingAs($user)->get(route('account.index'))
            ->assertOk()
            ->assertSee($this->embedUrl(), false);
    }

    public function test_guest_login_page_renders_tawk_widget(): void
    {
        $this->configure();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($this->embedUrl(), false);
    }

    public function test_widget_hidden_without_credentials_or_when_disabled(): void
    {
        // Belum dikonfigurasi -> tidak ada script.
        config(['tawk.property_id' => '', 'tawk.widget_key' => '']);
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('embed.tawk.to', false);

        // Dikonfigurasi tapi dimatikan lewat Pengaturan Website.
        $this->configure();
        Setting::putMany(['feature_live_chat' => '0']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('embed.tawk.to', false);
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('embed.tawk.to', false);
    }
}
