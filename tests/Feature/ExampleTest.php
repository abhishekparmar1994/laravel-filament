<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_admin_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }
}
