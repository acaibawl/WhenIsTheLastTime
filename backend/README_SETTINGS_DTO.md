# Settings DTO - 使用方法

## 概要

`settings_json`を型安全なオブジェクトとして扱うためのDTO（Data Transfer Object）クラス群とハイドレーター/デハイドレータークラスを提供します。

## クラス構成

### DTOクラス（readonly プロパティ）

```
Settings
├── ExportSettings
│   └── lastExportedAt: ?string
├── NotificationSettings
│   └── ReminderSettings
│       ├── enabled: bool
│       ├── timing: TimingSettings
│       │   ├── type: string
│       │   ├── time: string
│       │   ├── dayOfWeek: ?int
│       │   └── dayOfMonth: ?int
│       └── targetEvents: string
└── MiscSettings
    └── showTutorial: bool
```

### ユーティリティクラス

- `SettingsHydrator`: 配列 → オブジェクトへの変換
- `SettingsDehydrator`: オブジェクト → 配列への変換

## 使用方法

### 1. UserSettingモデルでの使用

```php
use App\Models\UserSetting;

$userSetting = UserSetting::find(1);

// オブジェクトとして取得
$settings = $userSetting->getSettings();

// readonly プロパティとして型安全にアクセス
echo $settings->export->lastExportedAt; // ?string
echo $settings->notification->reminder->enabled; // bool
echo $settings->notification->reminder->timing->type; // string
echo $settings->notification->reminder->timing->time; // string
echo $settings->notification->reminder->timing->dayOfWeek; // ?int
echo $settings->notification->reminder->targetEvents; // string
echo $settings->misc->showTutorial; // bool

// IDEの補完が効き、存在しないプロパティへのアクセスはエラーになる
// $settings->export->nonExistent; // ← PHPStanがエラーを検出
```

### 2. 設定の更新

```php
use App\DataTransferObjects\Settings\ExportSettings;
use App\DataTransferObjects\Settings\MiscSettings;
use App\DataTransferObjects\Settings\NotificationSettings;
use App\DataTransferObjects\Settings\ReminderSettings;
use App\DataTransferObjects\Settings\Settings;
use App\DataTransferObjects\Settings\TimingSettings;
use App\Services\Settings\SettingsDehydrator;

// 既存設定を取得
$currentSettings = $userSetting->getSettings();

// 新しい設定オブジェクトを作成（readonly なので、新しいインスタンスを作成）
$newSettings = new Settings(
    export: new ExportSettings(
        lastExportedAt: '2026-01-28T10:00:00Z'
    ),
    notification: new NotificationSettings(
        reminder: new ReminderSettings(
            enabled: true,
            timing: new TimingSettings(
                type: 'weekly',
                time: '18:00',
                dayOfWeek: 1,
                dayOfMonth: null
            ),
            targetEvents: 'month'
        )
    ),
    misc: new MiscSettings(
        showTutorial: false
    )
);

// モデルに保存
$userSetting->setSettings($newSettings);
$userSetting->save();
```

### 3. 部分的な更新

```php
use App\Services\Settings\SettingsHydrator;
use App\Services\Settings\SettingsDehydrator;

// 既存設定を配列として取得
$currentArray = $userSetting->settings_json;

// 部分的な更新データ
$updates = [
    'misc' => [
        'showTutorial' => false,
    ],
];

// マージ
$currentArray['misc'] = array_merge(
    $currentArray['misc'] ?? [],
    $updates['misc']
);

// オブジェクトに変換して確認
$settings = SettingsHydrator::hydrate($currentArray);
echo $settings->misc->showTutorial; // false

// モデルに保存
$userSetting->settings_json = $currentArray;
$userSetting->save();
```

### 4. コントローラーでの使用例

```php
use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use App\Services\Settings\SettingsHydrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $userSetting = $user->setting;

        // オブジェクトとして取得
        $settings = $userSetting->getSettings();

        // 型安全にアクセス
        if ($settings->notification->reminder->enabled) {
            // リマインダーが有効な場合の処理
            $reminderTime = $settings->notification->reminder->timing->time;
            // ...
        }

        return $this->successResponse([
            'settings' => $settings,
        ]);
    }
}
```

## 利点

### 1. 型安全性

```php
// 以前（配列アクセス）
$lastExportedAt = $userSetting->settings_json['export']['lastExportedAt'] ?? null; // 型が不明
$enabled = $userSetting->settings_json['notification']['reminder']['enabled'] ?? false; // タイポに気づけない

// 現在（オブジェクトアクセス）
$settings = $userSetting->getSettings();
$lastExportedAt = $settings->export->lastExportedAt; // ?string
$enabled = $settings->notification->reminder->enabled; // bool
```

### 2. IDEの補完

オブジェクトプロパティとしてアクセスできるため、IDEの補完が効きます。

```php
$settings = $userSetting->getSettings();
$settings->export->  // ← lastExportedAt が補完候補に表示される
$settings->notification->reminder->  // ← enabled, timing, targetEvents が表示される
```

### 3. PHPStanによる静的解析

存在しないプロパティへのアクセスやタイポをPHPStanが検出します。

```php
$settings = $userSetting->getSettings();
$settings->export->lastExportAt; // ← PHPStanがエラーを検出（lastExportedAtの間違い）
$settings->notification->remider; // ← PHPStanがエラーを検出（reminderの間違い）
```

### 4. リファクタリングの安全性

プロパティ名を変更する場合、IDEのリファクタリング機能が使えます。また、変更漏れがあればPHPStanが検出します。

## テスト

```php
use Tests\TestCase;
use App\Models\UserSetting;
use App\Services\Settings\SettingsHydrator;
use App\Services\Settings\SettingsDehydrator;

class SettingsTest extends TestCase
{
    public function test_settings_hydration(): void
    {
        $array = [
            'export' => ['lastExportedAt' => '2026-01-28T10:00:00Z'],
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
            'misc' => ['showTutorial' => false],
        ];

        $settings = SettingsHydrator::hydrate($array);

        $this->assertEquals('2026-01-28T10:00:00Z', $settings->export->lastExportedAt);
        $this->assertTrue($settings->notification->reminder->enabled);
        $this->assertEquals('weekly', $settings->notification->reminder->timing->type);
        $this->assertFalse($settings->misc->showTutorial);
    }

    public function test_settings_dehydration(): void
    {
        $array = [
            'export' => ['lastExportedAt' => null],
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
            'misc' => ['showTutorial' => true],
        ];

        $settings = SettingsHydrator::hydrate($array);
        $dehydrated = SettingsDehydrator::dehydrate($settings);

        $this->assertEquals($array, $dehydrated);
    }
}
```

## まとめ

- `settings_json`を型安全なオブジェクトとして扱えるようになりました
- readonly プロパティにより、不変性が保証されます
- IDEの補完とPHPStanの静的解析により、開発効率と安全性が向上します
- 既存のコードとの互換性を保ちながら、段階的に移行できます
