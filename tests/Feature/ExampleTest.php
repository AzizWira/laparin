<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_shows_the_public_catalog(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Tenant food court');
    }
}
