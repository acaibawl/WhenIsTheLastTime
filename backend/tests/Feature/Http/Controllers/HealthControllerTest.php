<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    /**
     * healthチェックのルートテスト
     */
    public function test_index(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);
    }
}
