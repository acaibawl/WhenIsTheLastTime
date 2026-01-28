<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 履歴一覧取得のテスト（正常系）
     */
    public function test_get_histories_successfully(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create([
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        // 複数の履歴を作成
        /** @var History $history1 */
        $history1 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(10),
            'memo' => '前回の掃除',
        ]);

        /** @var History $history2 */
        $history2 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
            'memo' => '中間の掃除',
        ]);

        /** @var History $history3 */
        $history3 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(1),
            'memo' => '最新の掃除',
        ]);

        // 最終実行履歴IDを更新
        $event->update(['last_executed_history_id' => $history3->id]);

        // 認証してリクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}/history");

        // レスポンスの検証
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'histories' => [
                        '*' => [
                            'id',
                            'eventId',
                            'executedAt',
                            'memo',
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
                    'histories' => [
                        [
                            'id' => $history3->id,
                            'eventId' => $event->id,
                            'memo' => '最新の掃除',
                        ],
                        [
                            'id' => $history2->id,
                            'eventId' => $event->id,
                            'memo' => '中間の掃除',
                        ],
                        [
                            'id' => $history1->id,
                            'eventId' => $event->id,
                            'memo' => '前回の掃除',
                        ],
                    ],
                ],
            ]);

        // 履歴が新しい順にソートされていることを確認
        $histories = $response->json('data.histories');
        $this->assertCount(3, $histories);
        $this->assertEquals($history3->id, $histories[0]['id']);
        $this->assertEquals($history2->id, $histories[1]['id']);
        $this->assertEquals($history1->id, $histories[2]['id']);
    }

    /**
     * 履歴一覧取得のテスト（履歴が1件のみ）
     */
    public function test_get_histories_with_single_history(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 履歴を1件だけ作成
        /** @var History $history */
        $history = History::factory()->for($event)->create([
            'executed_at' => now(),
            'memo' => '初回記録',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // 認証してリクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}/history");

        // レスポンスの検証
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'histories' => [
                        [
                            'id' => $history->id,
                            'eventId' => $event->id,
                            'memo' => '初回記録',
                        ],
                    ],
                ],
            ]);

        $histories = $response->json('data.histories');
        $this->assertCount(1, $histories);
    }

    /**
     * 履歴一覧取得のテスト（メモなしの履歴）
     */
    public function test_get_histories_without_memo(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // メモなしの履歴を作成
        /** @var History $history */
        $history = History::factory()->for($event)->create([
            'executed_at' => now(),
            'memo' => null,
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // 認証してリクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson("/events/{$event->id}/history");

        // レスポンスの検証
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'histories' => [
                        [
                            'id' => $history->id,
                            'eventId' => $event->id,
                            'memo' => null,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * 履歴一覧取得のテスト（存在しないイベントID）
     */
    public function test_get_histories_with_non_existent_event(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 存在しないイベントIDでリクエスト
        $response = $this->actingAs($user, 'api')
            ->getJson('/events/evt_nonexistent/history');

        // レスポンスの検証
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    /**
     * 履歴一覧取得のテスト（他ユーザーのイベント）
     */
    public function test_get_histories_of_other_users_event(): void
    {
        // ユーザーを2人作成
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // user2のイベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user2)->create();

        /** @var History $history */
        $history = History::factory()->for($event)->create();
        $event->update(['last_executed_history_id' => $history->id]);

        // user1として認証してuser2のイベントの履歴を取得しようとする
        $response = $this->actingAs($user1, 'api')
            ->getJson("/events/{$event->id}/history");

        // レスポンスの検証（アクセスできないはず）
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Event not found',
            ]);
    }

    /**
     * 履歴一覧取得のテスト（未認証）
     */
    public function test_get_histories_without_authentication(): void
    {
        // イベントを作成（認証なし）
        $user = User::factory()->create();
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 認証なしでリクエスト
        $response = $this->getJson("/events/{$event->id}/history");

        // レスポンスの検証
        // 認証なしの場合、401または404が返される可能性がある
        // Laravelのルート処理では、認証前にルートが見つからない場合は404が返される
        $this->assertTrue(
            $response->status() === 401 || $response->status() === 404,
            'Expected status code 401 or 404, got ' . $response->status()
        );
    }
}
