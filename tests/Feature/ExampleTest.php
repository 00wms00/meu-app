<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example adapted to current app behavior.
     */
    public function test_the_application_redirects_from_root_to_login(): void
    {
        $response = $this->get('/');

        // / redireciona para /dashboard, que por sua vez redireciona para /login para visitantes
        $response->assertStatus(302);
    }
}
