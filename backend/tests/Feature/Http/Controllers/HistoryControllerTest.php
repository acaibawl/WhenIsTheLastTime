<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
                'message' => 'Resource not found',
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

    /**
     * 履歴追加のテスト（正常系・メモあり）
     */
    public function test_store_history_successfully_with_memo(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create([
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        // 既存の履歴を作成
        /** @var History $oldHistory */
        $oldHistory = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(10),
            'memo' => '前回の掃除',
        ]);

        $event->update(['last_executed_history_id' => $oldHistory->id]);

        // 新しい履歴を追加
        $executedAt = now()->subDays(1)->toIso8601String();
        $response = $this->actingAs($user, 'api')
            ->postJson("/events/{$event->id}/history", [
                'executedAt' => $executedAt,
                'memo' => '今回は念入りに実施しました',
            ]);

        // レスポンスの検証
        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'history' => [
                        'id',
                        'eventId',
                        'executedAt',
                        'memo',
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
                    'history' => [
                        'eventId' => $event->id,
                        'memo' => '今回は念入りに実施しました',
                    ],
                ],
            ]);

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('histories', [
            'event_id' => $event->id,
            'memo' => '今回は念入りに実施しました',
        ]);

        // イベントの最終実行履歴が更新されていることを確認
        $event->refresh();
        $newHistoryId = $response->json('data.history.id');
        $this->assertEquals($newHistoryId, $event->last_executed_history_id);
    }

    /**
     * 履歴追加のテスト（正常系・メモなし）
     */
    public function test_store_history_successfully_without_memo(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 既存の履歴を作成
        /** @var History $oldHistory */
        $oldHistory = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
        ]);

        $event->update(['last_executed_history_id' => $oldHistory->id]);

        // 新しい履歴を追加（メモなし）
        $executedAt = now()->toIso8601String();
        $response = $this->actingAs($user, 'api')
            ->postJson("/events/{$event->id}/history", [
                'executedAt' => $executedAt,
            ]);

        // レスポンスの検証
        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'history' => [
                        'eventId' => $event->id,
                        'memo' => null,
                    ],
                ],
            ]);

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('histories', [
            'event_id' => $event->id,
            'memo' => null,
        ]);
    }

    /**
     * 履歴追加のテスト（バリデーションエラー）
     */
    #[DataProvider('validationErrorDataProvider')]
    public function test_store_history_validation_error(array $requestBody, string $expectedErrorField): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        /** @var History $history */
        $history = History::factory()->for($event)->create();
        $event->update(['last_executed_history_id' => $history->id]);

        // リクエスト実行
        $response = $this->actingAs($user, 'api')
            ->postJson("/events/{$event->id}/history", $requestBody);

        // レスポンスの検証
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$expectedErrorField]);
    }

    /**
     * バリデーションエラーのデータプロバイダー
     *
     * @return array<string, array{requestBody: array<string, mixed>, expectedErrorField: string}>
     */
    public static function validationErrorDataProvider(): array
    {
        return [
            'executedAtなし' => [
                'requestBody' => [
                    'memo' => '実行日時がありません',
                ],
                'expectedErrorField' => 'executedAt',
            ],
            '未来の日時' => [
                'requestBody' => [
                    'executedAt' => now()->addDays(1)->toIso8601String(),
                ],
                'expectedErrorField' => 'executedAt',
            ],
            '無効な日時形式' => [
                'requestBody' => [
                    'executedAt' => '2026-01-28 10:30:00', // ISO 8601形式ではない
                ],
                'expectedErrorField' => 'executedAt',
            ],
            'メモが長すぎる' => [
                'requestBody' => [
                    'executedAt' => now()->toIso8601String(),
                    'memo' => str_repeat('あ', 501), // 501文字（最大500文字）
                ],
                'expectedErrorField' => 'memo',
            ],
        ];
    }

    /**
     * 履歴追加のテスト（存在しないイベントID）
     */
    public function test_store_history_with_non_existent_event(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 存在しないイベントIDでリクエスト
        $response = $this->actingAs($user, 'api')
            ->postJson('/events/evt_nonexistent/history', [
                'executedAt' => now()->toIso8601String(),
            ]);

        // レスポンスの検証
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    /**
     * 履歴追加のテスト（他ユーザーのイベント）
     */
    public function test_store_history_to_other_users_event(): void
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

        // user1として認証してuser2のイベントに履歴を追加しようとする
        $response = $this->actingAs($user1, 'api')
            ->postJson("/events/{$event->id}/history", [
                'executedAt' => now()->toIso8601String(),
            ]);

        // レスポンスの検証（アクセスできないはず）
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    /**
     * 履歴追加のテスト（未認証）
     */
    public function test_store_history_without_authentication(): void
    {
        // イベントを作成（認証なし）
        $user = User::factory()->create();
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 認証なしでリクエスト
        $response = $this->postJson("/events/{$event->id}/history", [
            'executedAt' => now()->toIso8601String(),
        ]);

        // レスポンスの検証
        $this->assertTrue(
            $response->status() === 401 || $response->status() === 404,
            'Expected status code 401 or 404, got ' . $response->status()
        );
    }

    /**
     * 履歴追加のテスト（最終実行履歴の自動更新）
     */
    public function test_store_history_updates_last_executed_history_automatically(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 古い履歴を作成
        /** @var History $oldHistory */
        $oldHistory = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(10),
        ]);

        $event->update(['last_executed_history_id' => $oldHistory->id]);

        // 新しい履歴を追加（より最近の日時）
        $response = $this->actingAs($user, 'api')
            ->postJson("/events/{$event->id}/history", [
                'executedAt' => now()->subDays(1)->toIso8601String(),
            ]);

        $response->assertCreated();

        // イベントの最終実行履歴が新しい履歴に更新されていることを確認
        $event->refresh();
        $newHistoryId = $response->json('data.history.id');
        $this->assertEquals($newHistoryId, $event->last_executed_history_id);
        $this->assertNotEquals($oldHistory->id, $event->last_executed_history_id);
    }

    /**
     * 履歴追加のテスト（過去の履歴を追加しても最終実行履歴は最新のまま）
     */
    public function test_store_history_with_older_date_keeps_latest_as_last_executed(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 最新の履歴を作成
        /** @var History $latestHistory */
        $latestHistory = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(1),
        ]);

        $event->update(['last_executed_history_id' => $latestHistory->id]);

        // より古い日時の履歴を追加
        $response = $this->actingAs($user, 'api')
            ->postJson("/events/{$event->id}/history", [
                'executedAt' => now()->subDays(10)->toIso8601String(),
                'memo' => '過去の記録を追加',
            ]);

        $response->assertCreated();

        // イベントの最終実行履歴は最新のまま（latestHistory）であることを確認
        $event->refresh();
        $this->assertEquals($latestHistory->id, $event->last_executed_history_id);
    }

    /**
     * 履歴更新のテスト（正常系・メモあり）
     */
    public function test_update_history_successfully_with_memo(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create([
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        // 履歴を作成
        /** @var History $history */
        $history = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
            'memo' => '初回のメモ',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // 履歴を更新
        $newExecutedAt = now()->subDays(3)->toIso8601String();
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/{$history->id}", [
                'executedAt' => $newExecutedAt,
                'memo' => '更新後のメモ',
            ]);

        // レスポンスの検証
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'history' => [
                        'id',
                        'eventId',
                        'executedAt',
                        'memo',
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
                    'history' => [
                        'id' => $history->id,
                        'eventId' => $event->id,
                        'memo' => '更新後のメモ',
                    ],
                ],
            ]);

        // データベースが更新されていることを確認
        $this->assertDatabaseHas('histories', [
            'id' => $history->id,
            'event_id' => $event->id,
            'memo' => '更新後のメモ',
        ]);

        // 古いメモが存在しないことを確認
        $this->assertDatabaseMissing('histories', [
            'id' => $history->id,
            'memo' => '初回のメモ',
        ]);
    }

    /**
     * 履歴更新のテスト（正常系・メモをnullに）
     */
    public function test_update_history_successfully_clear_memo(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // メモありの履歴を作成
        /** @var History $history */
        $history = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
            'memo' => '削除予定のメモ',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        // メモをnullに更新
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/{$history->id}", [
                'executedAt' => now()->subDays(5)->toIso8601String(),
                'memo' => null,
            ]);

        // レスポンスの検証
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'history' => [
                        'id' => $history->id,
                        'memo' => null,
                    ],
                ],
            ]);

        // データベースでメモがnullになっていることを確認
        $this->assertDatabaseHas('histories', [
            'id' => $history->id,
            'memo' => null,
        ]);
    }

    /**
     * 履歴更新のテスト（最終実行履歴の自動更新）
     */
    public function test_update_history_updates_last_executed_history_automatically(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 複数の履歴を作成
        /** @var History $history1 */
        $history1 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(10),
        ]);

        /** @var History $history2 */
        $history2 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
        ]);

        // history2が最新として設定されている
        $event->update(['last_executed_history_id' => $history2->id]);

        // history1の日時を更新して最新にする
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/{$history1->id}", [
                'executedAt' => now()->subDays(1)->toIso8601String(),
            ]);

        $response->assertOk();

        // イベントの最終実行履歴がhistory1に更新されていることを確認
        $event->refresh();
        $this->assertEquals($history1->id, $event->last_executed_history_id);
    }

    /**
     * 履歴更新のテスト（古い日時に更新しても最終実行履歴は最新のまま）
     */
    public function test_update_history_with_older_date_keeps_latest_as_last_executed(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        // 複数の履歴を作成
        /** @var History $history1 */
        $history1 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(10),
        ]);

        /** @var History $history2 */
        $history2 = History::factory()->for($event)->create([
            'executed_at' => now()->subDays(5),
        ]);

        // history2が最新として設定されている
        $event->update(['last_executed_history_id' => $history2->id]);

        // history2の日時をより古い日時に更新
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/{$history2->id}", [
                'executedAt' => now()->subDays(20)->toIso8601String(),
            ]);

        $response->assertOk();

        // イベントの最終実行履歴がhistory1に更新されていることを確認
        $event->refresh();
        $this->assertEquals($history1->id, $event->last_executed_history_id);
    }

    /**
     * 履歴更新のテスト（バリデーションエラー）
     */
    #[DataProvider('updateValidationErrorDataProvider')]
    public function test_update_history_validation_error(array $requestBody, string $expectedErrorField): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        /** @var History $history */
        $history = History::factory()->for($event)->create();
        $event->update(['last_executed_history_id' => $history->id]);

        // リクエスト実行
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/{$history->id}", $requestBody);

        // レスポンスの検証
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$expectedErrorField]);
    }

    /**
     * 履歴更新のバリデーションエラーのデータプロバイダー
     *
     * @return array<string, array{requestBody: array<string, mixed>, expectedErrorField: string}>
     */
    public static function updateValidationErrorDataProvider(): array
    {
        return [
            'executedAtなし' => [
                'requestBody' => [
                    'memo' => '実行日時がありません',
                ],
                'expectedErrorField' => 'executedAt',
            ],
            '未来の日時' => [
                'requestBody' => [
                    'executedAt' => now()->addDays(1)->toIso8601String(),
                ],
                'expectedErrorField' => 'executedAt',
            ],
            '無効な日時形式' => [
                'requestBody' => [
                    'executedAt' => '2026-01-28 10:30:00', // ISO 8601形式ではない
                ],
                'expectedErrorField' => 'executedAt',
            ],
            'メモが長すぎる' => [
                'requestBody' => [
                    'executedAt' => now()->toIso8601String(),
                    'memo' => str_repeat('あ', 501), // 501文字（最大500文字）
                ],
                'expectedErrorField' => 'memo',
            ],
        ];
    }

    /**
     * 履歴更新のテスト（存在しない履歴ID）
     */
    public function test_update_history_with_non_existent_history(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // イベントを作成
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();

        /** @var History $history */
        $history = History::factory()->for($event)->create();
        $event->update(['last_executed_history_id' => $history->id]);

        // 存在しない履歴IDでリクエスト
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event->id}/history/hist_nonexistent", [
                'executedAt' => now()->toIso8601String(),
            ]);

        // レスポンスの検証
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'History not found',
            ]);
    }

    /**
     * 履歴更新のテスト（異なるイベントの履歴）
     */
    public function test_update_history_of_different_event(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 2つのイベントを作成
        /** @var Event $event1 */
        $event1 = Event::factory()->for($user)->create();

        /** @var Event $event2 */
        $event2 = Event::factory()->for($user)->create();

        /** @var History $history1 */
        $history1 = History::factory()->for($event1)->create();
        $event1->update(['last_executed_history_id' => $history1->id]);

        /** @var History $history2 */
        $history2 = History::factory()->for($event2)->create();
        $event2->update(['last_executed_history_id' => $history2->id]);

        // event1のURLでevent2の履歴を更新しようとする
        $response = $this->actingAs($user, 'api')
            ->putJson("/events/{$event1->id}/history/{$history2->id}", [
                'executedAt' => now()->toIso8601String(),
            ]);

        // レスポンスの検証（履歴が見つからないはず）
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'History not found',
            ]);
    }

    /**
     * 履歴更新のテスト（他ユーザーのイベントの履歴）
     */
    public function test_update_history_of_other_users_event(): void
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

        // user1として認証してuser2のイベントの履歴を更新しようとする
        $response = $this->actingAs($user1, 'api')
            ->putJson("/events/{$event->id}/history/{$history->id}", [
                'executedAt' => now()->toIso8601String(),
            ]);

        // レスポンスの検証（アクセスできないはず）
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    /**
     * 履歴更新のテスト（未認証）
     */
    public function test_update_history_without_authentication(): void
    {
        // イベントと履歴を作成
        $user = User::factory()->create();
        /** @var Event $event */
        $event = Event::factory()->for($user)->create();
        /** @var History $history */
        $history = History::factory()->for($event)->create();
        $event->update(['last_executed_history_id' => $history->id]);

        // 認証なしでリクエスト
        $response = $this->putJson("/events/{$event->id}/history/{$history->id}", [
            'executedAt' => now()->toIso8601String(),
        ]);

        // レスポンスの検証
        $this->assertTrue(
            $response->status() === 401 || $response->status() === 404,
            'Expected status code 401 or 404, got ' . $response->status()
        );
    }
}
