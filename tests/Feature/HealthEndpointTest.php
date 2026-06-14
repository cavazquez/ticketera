<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_endpoint_is_ok_when_database_is_reachable(): void
    {
        $this->get('/up')->assertOk();
    }
}
