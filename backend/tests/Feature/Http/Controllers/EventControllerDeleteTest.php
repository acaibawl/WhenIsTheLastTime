<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_event_successfully(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()
            ->for($user)
            ->create(['name' => 'エアコンフィルター掃除']);

        // 複数の履歴を作成
        $history1 = History::factory()
            ->for($event)
            ->create(['executed_at' => '2026-01-01 10:00:00']);
        $history2 = History::factory()
            ->for($event)
            ->create(['executed_at' => '2026-01-15 23:31:00']);

        $event->update(['last_executed_history_id' => $history2->id]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
                'meta' => [
                    'timestamp',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Event and all histories deleted successfully',
                ],
            ]);

        // イベントが削除されたことを確認
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);

        // 履歴もカスケード削除されたことを確認
        $this->assertDatabaseMissing('histories', [
            'id' => $history1->id,
        ]);
        $this->assertDatabaseMissing('histories', [
            'id' => $history2->id,
        ]);
    }

    public function test_delete_event_with_single_history(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()
            ->for($user)
            ->create(['name' => '運転免許更新']);

        // 履歴を1件だけ作成
        $history = History::factory()
            ->for($event)
            ->create(['executed_at' => '2026-01-15 23:31:00']);

        $event->update(['last_executed_history_id' => $history->id]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // イベントと履歴が削除されたことを確認
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
        $this->assertDatabaseMissing('histories', [
            'id' => $history->id,
        ]);
    }

    public function test_delete_event_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/events/evt_nonexistent');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    public function test_delete_event_of_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $event = Event::factory()
            ->for($user2)
            ->create(['name' => '他人のイベント']);

        $history = History::factory()
            ->for($event)
            ->create();

        $event->update(['last_executed_history_id' => $history->id]);

        // user1が user2のイベントを削除しようとする
        $response = $this->actingAs($user1, 'api')
            ->deleteJson("/events/{$event->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);

        // イベントが削除されていないことを確認
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
        $this->assertDatabaseHas('histories', [
            'id' => $history->id,
        ]);
    }

    public function test_delete_event_requires_authentication(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()
            ->for($user)
            ->create();

        $history = History::factory()
            ->for($event)
            ->create();

        $event->update(['last_executed_history_id' => $history->id]);

        // 認証なしでリクエスト
        $response = $this->deleteJson("/events/{$event->id}");

        $response->assertStatus(401);

        // イベントが削除されていないことを確認
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }

    public function test_delete_event_with_multiple_histories(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()
            ->for($user)
            ->create(['name' => '定期検診']);

        // 5件の履歴を作成
        $histories = [];
        for ($i = 1; $i <= 5; $i++) {
            $histories[] = History::factory()
                ->for($event)
                ->create([
                    'executed_at' => "2026-01-{$i} 10:00:00",
                    'memo' => "履歴 {$i}",
                ]);
        }

        $event->update(['last_executed_history_id' => $histories[4]->id]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // イベントが削除されたことを確認
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);

        // すべての履歴がカスケード削除されたことを確認
        foreach ($histories as $history) {
            $this->assertDatabaseMissing('histories', [
                'id' => $history->id,
            ]);
        }
    }
}
