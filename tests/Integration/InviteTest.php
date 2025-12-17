<?php

namespace Tests\Feature;

use Tests\TestCase;

class InviteTest extends TestCase
{
    /**
     * Test that the invite route returns the correct view and status.
     *
     * @return void
     */
    public function test_invite_route_returns_view()
    {
        $code = 'TESTCODE123';
        $response = $this->get("/invite/{$code}");

        $response->assertStatus(200);
        $response->assertViewIs('invite');
        $response->assertViewHas('code', $code);
    }

    /**
     * Test that the invite view contains the correct deep link.
     *
     * @return void
     */
    public function test_invite_view_contains_deep_link()
    {
        $code = 'TESTCODE123';
        $response = $this->get("/invite/{$code}");

        $response->assertSee("studify://join?code={$code}");
    }

    /**
     * Test that the invite view contains the fallback URL.
     *
     * @return void
     */
    public function test_invite_view_contains_fallback_url()
    {
        $code = 'TESTCODE123';
        $response = $this->get("/invite/{$code}");

        $response->assertSee(route('app.download'));
    }
}
