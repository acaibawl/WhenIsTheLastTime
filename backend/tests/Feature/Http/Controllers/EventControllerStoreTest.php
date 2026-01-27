<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_event_with_valid_data(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => 'エアコンフィルター掃除',
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
            'memo' => 'フィルターを水洗いした',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'event' => [
                        'id',
                        'userId',
                        'name',
                        'categoryIcon',
                        'lastExecutedHistoryId',
                        'lastExecutedAt',
                        'lastExecutedMemo',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
                'meta' => [
                    'timestamp',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'event' => [
                        'userId' => $user->id,
                        'name' => 'エアコンフィルター掃除',
                        'categoryIcon' => 'leaf',
                        'lastExecutedMemo' => 'フィルターを水洗いした',
                    ],
                ],
            ]);

        // データベースを確認
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        $event = Event::where('user_id', $user->id)->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->last_executed_history_id);

        $this->assertDatabaseHas('histories', [
            'event_id' => $event->id,
            'memo' => 'フィルターを水洗いした',
        ]);
    }

    public function test_create_event_without_memo(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => '運転免許更新',
            'categoryIcon' => 'folder',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event' => [
                        'userId' => $user->id,
                        'name' => '運転免許更新',
                        'categoryIcon' => 'folder',
                        'lastExecutedMemo' => null,
                    ],
                ],
            ]);
    }

    public function test_create_event_requires_name(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_event_requires_category_icon(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => 'テストイベント',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categoryIcon']);
    }

    public function test_create_event_requires_executed_at(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['executedAt']);
    }

    public function test_create_event_validates_name_length(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => str_repeat('あ', 101), // 101文字
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_event_validates_name_not_only_whitespace(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => '   ', // 空白のみ
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_event_validates_category_icon(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'invalid-icon',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categoryIcon']);
    }

    public function test_create_event_validates_future_date(): void
    {
        $user = User::factory()->create();

        $futureDate = now()->addDay()->toIso8601String();

        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'leaf',
            'executedAt' => $futureDate,
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['executedAt']);
    }

    public function test_create_event_validates_memo_length(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
            'memo' => str_repeat('あ', 501), // 501文字
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/events', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['memo']);
    }

    public function test_create_event_requires_authentication(): void
    {
        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'leaf',
            'executedAt' => '2026-01-15T23:31:00Z',
        ];

        $response = $this->postJson('/events', $requestData);

        $response->assertStatus(401);
    }

    public function test_create_event_accepts_various_date_formats(): void
    {
        $user = User::factory()->create();

        // ISO 8601形式のバリエーション
        $dateFormats = [
            '2026-01-15T23:31:00Z',
            '2026-01-15T23:31:00+00:00',
            '2026-01-15T23:31:00+09:00',
        ];

        foreach ($dateFormats as $dateFormat) {
            $requestData = [
                'name' => 'テストイベント_' . $dateFormat,
                'categoryIcon' => 'leaf',
                'executedAt' => $dateFormat,
            ];

            $response = $this->actingAs($user, 'api')
                ->postJson('/events', $requestData);

            $response->assertStatus(201);
        }
    }
}
