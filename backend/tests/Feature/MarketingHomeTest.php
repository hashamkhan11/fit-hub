<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_with_active_plans_and_core_features(): void
    {
        SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-'.uniqid(),
            'monthly_price' => 29,
            'yearly_price' => 290,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('QR Check-in');
        $response->assertSee('Classes &amp; Bookings', false);
        $response->assertSee('Billing &amp; Payments', false);
        $response->assertDontSee('Smart Lock');
        $response->assertDontSee('Fingerprint');
    }

    public function test_homepage_includes_seo_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('og:title', false);
        $response->assertSee('twitter:card', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('"@type":"SoftwareApplication"', false);
    }

    public function test_sitemap_is_reachable(): void
    {
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_manifest_and_icon_assets_exist(): void
    {
        $this->assertFileExists(public_path('site.webmanifest'));
        $this->assertFileExists(public_path('images/branding/favicon-32.png'));
        $this->assertFileExists(public_path('images/branding/apple-touch-icon.png'));
        $this->assertFileExists(public_path('images/branding/og-image.png'));
    }
}
