<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_events_returns_all_user_events(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントと履歴を作成
        $event1 = Event::factory()->create([
            'user_id' => $user->id,
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        $history1 = History::factory()->create([
            'event_id' => $event1->id,
            'executed_at' => now()->subDays(10),
            'memo' => 'フィルターを水洗いした',
        ]);

        $event1->update(['last_executed_history_id' => $history1->id]);

        $event2 = Event::factory()->create([
            'user_id' => $user->id,
            'name' => '運転免許更新',
            'category_icon' => 'folder',
        ]);

        $history2 = History::factory()->create([
            'event_id' => $event2->id,
            'executed_at' => now()->subDays(5),
            'memo' => null,
        ]);

        $event2->update(['last_executed_history_id' => $history2->id]);

        // 別ユーザーのイベント（表示されないことを確認）
        $otherUser = User::factory()->create();
        Event::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // リクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson('/events');

        // レスポンス検証
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'events' => [
                        '*' => [
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
                ],
                'meta' => [
                    'timestamp',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'events' => [
                        [
                            'id' => $event1->id,
                            'userId' => $user->id,
                            'name' => 'エアコンフィルター掃除',
                            'categoryIcon' => 'leaf',
                            'lastExecutedHistoryId' => $history1->id,
                            'lastExecutedMemo' => 'フィルターを水洗いした',
                        ],
                        [
                            'id' => $event2->id,
                            'userId' => $user->id,
                            'name' => '運転免許更新',
                            'categoryIcon' => 'folder',
                            'lastExecutedHistoryId' => $history2->id,
                            'lastExecutedMemo' => null,
                        ],
                    ],
                ],
            ]);

        // 2件のイベントのみ返されることを確認
        $this->assertCount(2, $response->json('data.events'));
    }

    public function test_get_events_returns_empty_array_when_no_events(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/events');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'events' => [],
                ],
            ]);
    }

    public function test_get_events_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/events');

        $response->assertStatus(401);
    }

    public function test_get_events_returns_event_without_history(): void
    {
        $user = User::factory()->create();

        // 履歴のないイベントを作成
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'name' => '新規イベント',
            'category_icon' => 'star',
            'last_executed_history_id' => null,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/events');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'events' => [
                        [
                            'id' => $event->id,
                            'name' => '新規イベント',
                            'categoryIcon' => 'star',
                            'lastExecutedHistoryId' => null,
                            'lastExecutedAt' => null,
                            'lastExecutedMemo' => null,
                        ],
                    ],
                ],
            ]);
    }

    public function test_get_events_returns_events_with_iso8601_date_format(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $history = History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2026-01-15 23:31:00',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/events');

        $response->assertStatus(200);

        $eventData = $response->json('data.events.0');

        // ISO 8601形式であることを確認
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $eventData['lastExecutedAt']
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $eventData['createdAt']
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $eventData['updatedAt']
        );
    }
}
