<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

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

    /**
     * 設定更新が正常に行われる
     */
    public function test_update_settings_successfully(): void
    {
        // 初期設定を作成
        UserSetting::create([
            'user_id' => $this->user->id,
            'settings_json' => UserSetting::getDefaultSettings(),
        ]);

        $updateData = [
            'notification' => [
                'reminder' => [
                    'enabled' => true,
                    'timing' => [
                        'type' => 'weekly',
                        'time' => '18:00',
                        'dayOfWeek' => 1,
                    ],
                    'targetEvents' => 'month',
                ],
            ],
            'misc' => [
                'showTutorial' => false,
            ],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson('/settings', $updateData);

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

        // 更新された値が反映されていることを確認
        $settings = $response->json('data.settings');

        $this->assertTrue($settings['notification']['reminder']['enabled']);
        $this->assertEquals('weekly', $settings['notification']['reminder']['timing']['type']);
        $this->assertEquals('18:00', $settings['notification']['reminder']['timing']['time']);
        $this->assertEquals(1, $settings['notification']['reminder']['timing']['dayOfWeek']);
        $this->assertEquals('month', $settings['notification']['reminder']['targetEvents']);
        $this->assertFalse($settings['misc']['showTutorial']);

        // exportは更新していないのでデフォルト値のまま
        $this->assertNull($settings['export']['lastExportedAt']);
    }

    /**
     * 部分更新が正常に行われる
     */
    public function test_update_settings_partial_update(): void
    {
        // 初期設定を作成
        $initialSettings = [
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
            'settings_json' => $initialSettings,
        ]);

        // misc.showTutorial のみを更新
        $updateData = [
            'misc' => [
                'showTutorial' => true,
            ],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson('/settings', $updateData);

        $response->assertStatus(200);

        $settings = $response->json('data.settings');

        // misc.showTutorial が更新されている
        $this->assertTrue($settings['misc']['showTutorial']);

        // その他の設定は変更されていない
        $this->assertEquals('2026-01-15T10:00:00Z', $settings['export']['lastExportedAt']);
        $this->assertTrue($settings['notification']['reminder']['enabled']);
        $this->assertEquals('weekly', $settings['notification']['reminder']['timing']['type']);
        $this->assertEquals('18:00', $settings['notification']['reminder']['timing']['time']);
        $this->assertEquals(1, $settings['notification']['reminder']['timing']['dayOfWeek']);
        $this->assertEquals('month', $settings['notification']['reminder']['targetEvents']);
    }

    /**
     * バリデーションエラーが正しく返される
     */
    public function test_update_settings_validation_errors(): void
    {
        UserSetting::create([
            'user_id' => $this->user->id,
            'settings_json' => UserSetting::getDefaultSettings(),
        ]);

        // 不正な型のデータ
        $invalidData = [
            'notification' => [
                'reminder' => [
                    'enabled' => 'invalid',  // boolean ではない
                    'timing' => [
                        'type' => 'invalid_type',  // 許可されていない値
                        'time' => '25:00',  // 不正な時刻形式
                        'dayOfWeek' => 10,  // 範囲外
                    ],
                ],
            ],
            'misc' => [
                'showTutorial' => 'yes',  // boolean ではない
            ],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson('/settings', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    /**
     * 設定が存在しない場合でも更新できる
     */
    public function test_update_settings_creates_settings_when_not_exists(): void
    {
        // ユーザー設定を削除
        $this->user->setting?->delete();
        $this->user->refresh();
        $this->assertNull($this->user->setting);

        $updateData = [
            'misc' => [
                'showTutorial' => false,
            ],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson('/settings', $updateData);

        $response->assertStatus(200);

        $settings = $response->json('data.settings');

        // 更新された値が反映されている
        $this->assertFalse($settings['misc']['showTutorial']);

        // その他はデフォルト値
        $this->assertNull($settings['export']['lastExportedAt']);
        $this->assertFalse($settings['notification']['reminder']['enabled']);
    }

    /**
     * 未認証の場合、401エラーが返される
     */
    public function test_update_settings_requires_authentication(): void
    {
        $response = $this->patchJson('/settings', [
            'misc' => [
                'showTutorial' => false,
            ],
        ]);

        $response->assertStatus(401);
    }
}
