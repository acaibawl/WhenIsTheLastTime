<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * CSVエクスポートが成功する
     */
    public function test_export_csv_returns_csv_file(): void
    {
        // イベントと履歴を作成
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'エアコンフィルター掃除',
            'category_icon' => 'leaf',
        ]);

        $history = History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2026-01-15 23:31:00',
            'memo' => 'フィルターを水洗いした',
        ]);

        $event->update(['last_executed_history_id' => $history->id]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        // Content-Typeの確認
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        // Content-Dispositionの確認
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('when-is-the-last-time_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        // CSVの内容を確認
        $content = $response->streamedContent();

        // BOMの確認（最初の3バイト）
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // ヘッダー行の確認（fputcsvはスペースを含むとダブルクォートで囲む）
        $this->assertStringContainsString('Event name', $content);
        $this->assertStringContainsString('Note', $content);
        $this->assertStringContainsString('Created at', $content);

        // イベント名とメモの確認
        $this->assertStringContainsString('エアコンフィルター掃除', $content);
        $this->assertStringContainsString('フィルターを水洗いした', $content);
    }

    /**
     * 複数イベントと履歴のエクスポート
     */
    public function test_export_csv_with_multiple_events_and_histories(): void
    {
        // イベント1
        $event1 = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'ギター弦交換',
            'category_icon' => 'star',
        ]);

        History::factory()->create([
            'event_id' => $event1->id,
            'executed_at' => '2021-06-25 11:55:52',
            'memo' => 'SIT CRT 010 - 046',
        ]);

        History::factory()->create([
            'event_id' => $event1->id,
            'executed_at' => '2021-07-18 04:25:36',
            'memo' => 'SIT ニッケル 010-046',
        ]);

        // イベント2
        $event2 = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => '読書',
            'category_icon' => 'book',
        ]);

        History::factory()->create([
            'event_id' => $event2->id,
            'executed_at' => '2021-01-09 00:33:39',
            'memo' => 'イベント作成済み',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // 両方のイベントが含まれていることを確認
        $this->assertStringContainsString('ギター弦交換', $content);
        $this->assertStringContainsString('読書', $content);

        // 履歴のメモが含まれていることを確認
        $this->assertStringContainsString('SIT CRT 010 - 046', $content);
        $this->assertStringContainsString('SIT ニッケル 010-046', $content);
        $this->assertStringContainsString('イベント作成済み', $content);
    }

    /**
     * 履歴がないイベントのエクスポート
     */
    public function test_export_csv_event_without_history(): void
    {
        Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => '新規イベント',
            'category_icon' => 'pin',
            'last_executed_history_id' => null,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // イベント名とデフォルトメモが含まれていることを確認
        $this->assertStringContainsString('新規イベント', $content);
        $this->assertStringContainsString('イベント作成済み', $content);
    }

    /**
     * イベントがない場合、ヘッダーのみのCSVが返される
     */
    public function test_export_csv_with_no_events(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // ヘッダー行が含まれていることを確認
        $this->assertStringContainsString('Event name', $content);
        $this->assertStringContainsString('Note', $content);
        $this->assertStringContainsString('Created at', $content);

        // データ行がないことを確認（ヘッダー行とBOMのみ）
        $lines = array_filter(explode("\n", mb_trim($content)), fn ($line) => $line !== '');
        $this->assertCount(1, $lines);
    }

    /**
     * 他のユーザーのイベントはエクスポートされない
     */
    public function test_export_csv_excludes_other_users_events(): void
    {
        // 他のユーザーのイベント
        $otherUser = User::factory()->create();
        Event::factory()->create([
            'user_id' => $otherUser->id,
            'name' => '他のユーザーのイベント',
        ]);

        // 自分のイベント
        Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => '自分のイベント',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // 自分のイベントのみ含まれていることを確認
        $this->assertStringContainsString('自分のイベント', $content);
        $this->assertStringNotContainsString('他のユーザーのイベント', $content);
    }

    /**
     * 未認証の場合は401エラー
     */
    public function test_export_csv_requires_authentication(): void
    {
        $response = $this->getJson('/export/csv');

        $response->assertStatus(401);
    }

    /**
     * メモに改行が含まれる場合も正しくCSV出力される
     */
    public function test_export_csv_handles_multiline_memo(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'ギター弦交換',
        ]);

        History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2021-07-31 14:53:08',
            'memo' => "SIT ニッケル 010-046\nフレットバター\nナットソース",
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // 改行を含むメモがダブルクオートで囲まれていることを確認
        $this->assertStringContainsString('SIT ニッケル 010-046', $content);
        $this->assertStringContainsString('フレットバター', $content);
        $this->assertStringContainsString('ナットソース', $content);
    }

    /**
     * 履歴がexecuted_at順にソートされている
     */
    public function test_export_csv_histories_sorted_by_executed_at(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'テストイベント',
        ]);

        // 順番をバラバラに作成
        History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2021-03-01 10:00:00',
            'memo' => '3番目',
        ]);

        History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2021-01-01 10:00:00',
            'memo' => '1番目',
        ]);

        History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2021-02-01 10:00:00',
            'memo' => '2番目',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // 順序を確認（1番目が2番目より前、2番目が3番目より前）
        $pos1 = mb_strpos($content, '1番目');
        $pos2 = mb_strpos($content, '2番目');
        $pos3 = mb_strpos($content, '3番目');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertLessThan($pos2, $pos1);
        $this->assertLessThan($pos3, $pos2);
    }

    /**
     * CSVの日付形式がタイムゾーン情報付きである
     */
    public function test_export_csv_datetime_format_with_timezone(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'テストイベント',
        ]);

        History::factory()->create([
            'event_id' => $event->id,
            'executed_at' => '2026-01-15 23:31:00',
            'memo' => 'テストメモ',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get('/export/csv');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // タイムゾーン情報（+0000 or +0900など）が含まれていることを確認
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} [+-]\d{4}/',
            $content
        );
    }
}
