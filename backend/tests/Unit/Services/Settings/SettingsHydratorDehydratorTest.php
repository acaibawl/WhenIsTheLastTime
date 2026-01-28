<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Settings;

use App\DataTransferObjects\Settings\Settings;
use App\Services\Settings\SettingsDehydrator;
use App\Services\Settings\SettingsHydrator;
use Tests\TestCase;

class SettingsHydratorDehydratorTest extends TestCase
{
    /**
     * ハイドレーターがデフォルト設定を正しく変換することをテスト
     */
    public function test_hydrate_default_settings(): void
    {
        $array = [
            'export' => [
                'lastExportedAt' => null,
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => false,
                    'timing' => [
                        'type' => 'daily',
                        'time' => '09:00',
                        'dayOfWeek' => null,
                        'dayOfMonth' => null,
                    ],
                    'targetEvents' => 'week',
                ],
            ],
            'misc' => [
                'showTutorial' => true,
            ],
        ];

        $settings = SettingsHydrator::hydrate($array);

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertNull($settings->export->lastExportedAt);
        $this->assertFalse($settings->notification->reminder->enabled);
        $this->assertEquals('daily', $settings->notification->reminder->timing->type);
        $this->assertEquals('09:00', $settings->notification->reminder->timing->time);
        $this->assertNull($settings->notification->reminder->timing->dayOfWeek);
        $this->assertNull($settings->notification->reminder->timing->dayOfMonth);
        $this->assertEquals('week', $settings->notification->reminder->targetEvents);
        $this->assertTrue($settings->misc->showTutorial);
    }

    /**
     * ハイドレーターがカスタム設定を正しく変換することをテスト
     */
    public function test_hydrate_custom_settings(): void
    {
        $array = [
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

        $settings = SettingsHydrator::hydrate($array);

        $this->assertEquals('2026-01-15T10:00:00Z', $settings->export->lastExportedAt);
        $this->assertTrue($settings->notification->reminder->enabled);
        $this->assertEquals('weekly', $settings->notification->reminder->timing->type);
        $this->assertEquals('18:00', $settings->notification->reminder->timing->time);
        $this->assertEquals(1, $settings->notification->reminder->timing->dayOfWeek);
        $this->assertEquals('month', $settings->notification->reminder->targetEvents);
        $this->assertFalse($settings->misc->showTutorial);
    }

    /**
     * デハイドレーターがオブジェクトを正しく配列に変換することをテスト
     */
    public function test_dehydrate_settings(): void
    {
        $array = [
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

        $settings = SettingsHydrator::hydrate($array);
        $dehydrated = SettingsDehydrator::dehydrate($settings);

        $this->assertEquals($array, $dehydrated);
    }

    /**
     * ハイドレーター→デハイドレーターの往復変換をテスト
     */
    public function test_hydrate_dehydrate_round_trip(): void
    {
        $original = [
            'export' => [
                'lastExportedAt' => null,
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => false,
                    'timing' => [
                        'type' => 'daily',
                        'time' => '09:00',
                        'dayOfWeek' => null,
                        'dayOfMonth' => null,
                    ],
                    'targetEvents' => 'week',
                ],
            ],
            'misc' => [
                'showTutorial' => true,
            ],
        ];

        $settings = SettingsHydrator::hydrate($original);
        $result = SettingsDehydrator::dehydrate($settings);

        $this->assertEquals($original, $result);
    }

    /**
     * 部分的なデータでもハイドレートできることをテスト
     */
    public function test_hydrate_with_partial_data(): void
    {
        $array = [
            'misc' => [
                'showTutorial' => false,
            ],
        ];

        $settings = SettingsHydrator::hydrate($array);

        // デフォルト値が設定されることを確認
        $this->assertNull($settings->export->lastExportedAt);
        $this->assertFalse($settings->notification->reminder->enabled);
        $this->assertEquals('daily', $settings->notification->reminder->timing->type);
        $this->assertEquals('09:00', $settings->notification->reminder->timing->time);
        $this->assertEquals('week', $settings->notification->reminder->targetEvents);

        // 指定した値が反映されることを確認
        $this->assertFalse($settings->misc->showTutorial);
    }

    /**
     * 空の配列でもハイドレートできることをテスト
     */
    public function test_hydrate_with_empty_array(): void
    {
        $settings = SettingsHydrator::hydrate([]);

        // すべてデフォルト値が設定されることを確認
        $this->assertNull($settings->export->lastExportedAt);
        $this->assertFalse($settings->notification->reminder->enabled);
        $this->assertEquals('daily', $settings->notification->reminder->timing->type);
        $this->assertEquals('09:00', $settings->notification->reminder->timing->time);
        $this->assertNull($settings->notification->reminder->timing->dayOfWeek);
        $this->assertNull($settings->notification->reminder->timing->dayOfMonth);
        $this->assertEquals('week', $settings->notification->reminder->targetEvents);
        $this->assertTrue($settings->misc->showTutorial);
    }

    /**
     * UserSettingモデルの統合テスト
     */
    public function test_user_setting_model_integration(): void
    {
        $userSetting = new \App\Models\UserSetting();
        $userSetting->settings_json = [
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

        // getSettings() でオブジェクトとして取得
        $settings = $userSetting->getSettings();

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertEquals('2026-01-15T10:00:00Z', $settings->export->lastExportedAt);
        $this->assertTrue($settings->notification->reminder->enabled);
        $this->assertEquals('weekly', $settings->notification->reminder->timing->type);
        $this->assertFalse($settings->misc->showTutorial);

        // 元の配列と一致することを確認
        $dehydrated = SettingsDehydrator::dehydrate($settings);
        $this->assertEquals($userSetting->settings_json, $dehydrated);
    }

    /**
     * merge メソッドが部分的な更新を正しく行うことをテスト
     */
    public function test_merge_partial_update(): void
    {
        // 元の設定
        $original = SettingsHydrator::hydrate([
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
        ]);

        // misc.showTutorial のみを更新
        $updated = SettingsHydrator::merge($original, [
            'misc' => [
                'showTutorial' => true,
            ],
        ]);

        // misc.showTutorial が更新されている
        $this->assertTrue($updated->misc->showTutorial);

        // その他の設定は変更されていない
        $this->assertEquals('2026-01-15T10:00:00Z', $updated->export->lastExportedAt);
        $this->assertTrue($updated->notification->reminder->enabled);
        $this->assertEquals('weekly', $updated->notification->reminder->timing->type);
        $this->assertEquals('18:00', $updated->notification->reminder->timing->time);
        $this->assertEquals(1, $updated->notification->reminder->timing->dayOfWeek);
        $this->assertEquals('month', $updated->notification->reminder->targetEvents);
    }

    /**
     * merge メソッドが複数の設定を同時に更新することをテスト
     */
    public function test_merge_multiple_updates(): void
    {
        $original = SettingsHydrator::hydrate([
            'export' => [
                'lastExportedAt' => null,
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => false,
                    'timing' => [
                        'type' => 'daily',
                        'time' => '09:00',
                        'dayOfWeek' => null,
                        'dayOfMonth' => null,
                    ],
                    'targetEvents' => 'week',
                ],
            ],
            'misc' => [
                'showTutorial' => true,
            ],
        ]);

        $updated = SettingsHydrator::merge($original, [
            'export' => [
                'lastExportedAt' => '2026-01-28T12:00:00Z',
            ],
            'notification' => [
                'reminder' => [
                    'enabled' => true,
                    'timing' => [
                        'type' => 'weekly',
                        'time' => '20:00',
                        'dayOfWeek' => 5,
                    ],
                    'targetEvents' => 'month',
                ],
            ],
            'misc' => [
                'showTutorial' => false,
            ],
        ]);

        // すべての更新が反映されている
        $this->assertEquals('2026-01-28T12:00:00Z', $updated->export->lastExportedAt);
        $this->assertTrue($updated->notification->reminder->enabled);
        $this->assertEquals('weekly', $updated->notification->reminder->timing->type);
        $this->assertEquals('20:00', $updated->notification->reminder->timing->time);
        $this->assertEquals(5, $updated->notification->reminder->timing->dayOfWeek);
        $this->assertEquals('month', $updated->notification->reminder->targetEvents);
        $this->assertFalse($updated->misc->showTutorial);
    }

    /**
     * merge メソッドがネストされた設定の部分更新を正しく行うことをテスト
     */
    public function test_merge_nested_partial_update(): void
    {
        $original = SettingsHydrator::hydrate([
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
        ]);

        // timing.time のみを更新
        $updated = SettingsHydrator::merge($original, [
            'notification' => [
                'reminder' => [
                    'timing' => [
                        'time' => '21:00',
                    ],
                ],
            ],
        ]);

        // timing.time のみが更新されている
        $this->assertEquals('21:00', $updated->notification->reminder->timing->time);

        // その他の timing 設定は変更されていない
        $this->assertEquals('weekly', $updated->notification->reminder->timing->type);
        $this->assertEquals(1, $updated->notification->reminder->timing->dayOfWeek);
        $this->assertNull($updated->notification->reminder->timing->dayOfMonth);

        // reminder の他の設定も変更されていない
        $this->assertTrue($updated->notification->reminder->enabled);
        $this->assertEquals('month', $updated->notification->reminder->targetEvents);
    }

    /**
     * merge メソッドが null 値を正しく更新することをテスト
     */
    public function test_merge_with_null_values(): void
    {
        $original = SettingsHydrator::hydrate([
            'notification' => [
                'reminder' => [
                    'timing' => [
                        'type' => 'weekly',
                        'time' => '18:00',
                        'dayOfWeek' => 1,
                        'dayOfMonth' => 15,
                    ],
                ],
            ],
        ]);

        // dayOfWeek と dayOfMonth を null に設定
        $updated = SettingsHydrator::merge($original, [
            'notification' => [
                'reminder' => [
                    'timing' => [
                        'dayOfWeek' => null,
                        'dayOfMonth' => null,
                    ],
                ],
            ],
        ]);

        $this->assertNull($updated->notification->reminder->timing->dayOfWeek);
        $this->assertNull($updated->notification->reminder->timing->dayOfMonth);

        // その他の設定は変更されていない
        $this->assertEquals('weekly', $updated->notification->reminder->timing->type);
        $this->assertEquals('18:00', $updated->notification->reminder->timing->time);
    }
}
