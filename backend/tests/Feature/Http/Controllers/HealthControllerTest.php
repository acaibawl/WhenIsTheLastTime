<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

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
