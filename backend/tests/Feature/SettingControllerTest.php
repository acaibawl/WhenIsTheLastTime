<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->user = User::factory()->create();
    }

    /**
     * ユーザー設定が存在しない場合、デフォルト設定が作成されて返される
     * （通常はユーザー作成時に設定も作成されるが、万が一存在しない場合のフォールバック）
     */
    public function test_get_settings_creates_default_settings_when_not_exists(): void
    {
        // ユーザー設定を手動で削除（異常系のテスト）
        $this->user->setting?->delete();
        $this->user->refresh();
        $this->assertNull($this->user->setting);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'settings' => [
                        'export',
                        'notification',
                        'misc',
                    ],
                ],
                'meta' => [
                    'timestamp',
                ],
            ]);

        // デフォルト値が返されることを確認
        $settings = $response->json('data.settings');

        $this->assertIsArray($settings['export']);
        $this->assertNull($settings['export']['lastExportedAt']);

        $this->assertIsArray($settings['notification']['reminder']);
        $this->assertFalse($settings['notification']['reminder']['enabled']);
        $this->assertEquals('daily', $settings['notification']['reminder']['timing']['type']);
        $this->assertEquals('09:00', $settings['notification']['reminder']['timing']['time']);
        $this->assertEquals('week', $settings['notification']['reminder']['targetEvents']);

        $this->assertIsArray($settings['misc']);
        $this->assertTrue($settings['misc']['showTutorial']);

        // データベースに設定が作成されたことを確認
        $this->assertNotNull($this->user->fresh()->setting);
    }

    /**
     * ユーザー設定が存在する場合、既存の設定が返される
     */
    public function test_get_settings_returns_existing_settings(): void
    {
        // カスタム設定を作成
        $customSettings = [
            'export' => [
                'lastExportedAt' => '2026-01-15T10:00:00Z',
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => true,
                    'timing' => [
                        'type' => 'weekly',
                        'time' => '18:00',
                        'dayOfWeek' => 1,
                        'dayOfMonth' => null,
                    ],
                    'targetEvents' => 'month',
                ],
            ],
            'misc' => [
                'showTutorial' => false,
            ],
        ];

        UserSetting::create([
            'user_id' => $this->user->id,
            'settings_json' => $customSettings,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/settings');

        $response->assertStatus(200);

        // カスタム設定が返されることを確認
        $settings = $response->json('data.settings');

        $this->assertEquals('2026-01-15T10:00:00Z', $settings['export']['lastExportedAt']);
        $this->assertTrue($settings['notification']['reminder']['enabled']);
        $this->assertEquals('weekly', $settings['notification']['reminder']['timing']['type']);
        $this->assertEquals('18:00', $settings['notification']['reminder']['timing']['time']);
        $this->assertEquals(1, $settings['notification']['reminder']['timing']['dayOfWeek']);
        $this->assertEquals('month', $settings['notification']['reminder']['targetEvents']);
        $this->assertFalse($settings['misc']['showTutorial']);
    }

    /**
     * 未認証の場合、401エラーが返される
     */
    public function test_get_settings_requires_authentication(): void
    {
        $response = $this->getJson('/settings');

        $response->assertStatus(401);
    }
}
