<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_event_by_id_returns_event_details(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントと履歴を作成
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        $history = History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => now()->subDays(10),
            'memo' => 'フィルターを水洗いした',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // リクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}");

        // レスポンス検証
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
                        'name' => 'エアコンフィルター掃除',
                        'categoryIcon' => 'leaf',
                        'lastExecutedHistoryId' => $history->id,
                        'lastExecutedMemo' => 'フィルターを水洗いした',
                    ],
                ],
            ]);

        // lastExecutedAt が正しく返されることを確認
        $this->assertNotNull($response->json('data.event.lastExecutedAt'));
    }

    public function test_get_event_by_id_returns_404_for_nonexistent_event(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 存在しないイベントIDを指定
        $nonexistentId = '01JKJT9G00AAAAAAAAAAAAAAAA';

        // リクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$nonexistentId}");

        // レスポンス検証
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    public function test_get_event_by_id_returns_404_for_other_users_event(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 別ユーザーのイベントを作成
        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $history = History::factory()->create([
            'event_id' => $event->id,
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // リクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}");

        // レスポンス検証（他のユーザーのイベントは見えない）
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    public function test_get_event_requires_authentication(): void
    {
        // ユーザーとイベントを作成
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        // 認証なしでリクエスト
        $response = $this->getJson("/events/{$event->id}");

        // 401 Unauthorized を返すことを確認
        $response->assertStatus(401);
    }

    public function test_get_event_with_no_history_returns_null_for_last_executed_fields(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 履歴のないイベントを作成（通常は起こらないが、テストケースとして）
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'name' => 'テストイベント',
            'category_icon' => 'star',
            'last_executed_history_id' => null,
        ]);

        // リクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}");

        // レスポンス検証
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'userId' => $user->id,
                        'name' => 'テストイベント',
                        'categoryIcon' => 'star',
                        'lastExecutedHistoryId' => null,
                        'lastExecutedAt' => null,
                        'lastExecutedMemo' => null,
                    ],
                ],
            ]);
    }
}
