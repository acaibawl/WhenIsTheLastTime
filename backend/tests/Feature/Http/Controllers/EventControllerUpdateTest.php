<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_event_with_valid_data(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create([
            'name' => '元のイベント名',
            'category_icon' => 'star',
        ]);

        $requestData = [
            'name' => 'エアコンフィルター掃除（更新）',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(200)
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
                        'id' => $event->id,
                        'userId' => $user->id,
                        'name' => 'エアコンフィルター掃除（更新）',
                        'categoryIcon' => 'leaf',
                    ],
                ],
            ]);

        // データベースを確認
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'user_id' => $user->id,
            'name' => 'エアコンフィルター掃除（更新）',
            'category_icon' => 'leaf',
        ]);
    }

    public function test_update_event_returns_404_when_event_not_found(): void
    {
        $user = User::factory()->create();

        $requestData = [
            'name' => '更新イベント',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson('/events/non-existent-id', $requestData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    public function test_update_event_returns_404_when_event_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $event = Event::factory()->for($anotherUser)->create();

        $requestData = [
            'name' => '更新イベント',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);

        // 元のイベントが変更されていないことを確認
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => $event->name,
            'category_icon' => $event->category_icon,
        ]);
    }

    public function test_update_event_requires_name(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['イベント名は必須です'],
            ]);
    }

    public function test_update_event_requires_category_icon(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'name' => 'テストイベント',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categoryIcon'])
            ->assertJsonFragment([
                'categoryIcon' => ['カテゴリーアイコンは必須です'],
            ]);
    }

    public function test_update_event_validates_name_length(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'name' => str_repeat('あ', 101), // 101文字
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['イベント名は100文字以内で入力してください'],
            ]);
    }

    public function test_update_event_validates_name_not_only_whitespace(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'name' => '   ', // 空白のみ
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJsonFragment([
                'name' => ['イベント名は必須です'],
            ]);
    }

    public function test_update_event_validates_category_icon(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'name' => 'テストイベント',
            'categoryIcon' => 'invalid-icon',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['categoryIcon'])
            ->assertJsonFragment([
                'categoryIcon' => ['無効なカテゴリーアイコンです'],
            ]);
    }

    public function test_update_event_requires_authentication(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->for($user)->create();

        $requestData = [
            'name' => '更新イベント',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(401);
    }

    public function test_update_event_preserves_last_executed_history(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()
            ->for($user)
            ->hasHistories(2)
            ->create([
                'name' => '元のイベント名',
                'category_icon' => 'star',
            ]);

        // 最新の履歴をlast_executed_history_idに設定
        $latestHistory = $event->histories()->orderBy('executed_at', 'desc')->first();
        $event->update(['last_executed_history_id' => $latestHistory->id]);

        $requestData = [
            'name' => '更新されたイベント名',
            'categoryIcon' => 'leaf',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}", $requestData);

        $response->assertStatus(200);

        // 履歴が保持されていることを確認
        $event->refresh();
        $this->assertEquals($latestHistory->id, $event->last_executed_history_id);
        $this->assertEquals(2, $event->histories()->count());
    }
}
