# イベント一覧取得API実装

## 概要

メイン画面のイベント一覧取得APIを実装しました。認証ユーザーの全イベントを取得し、最後の履歴情報も含めて返却します。

## 実装内容

### 1. EventController の作成

**ファイル**: `app/Http/Controllers/EventController.php`

#### index メソッド

- 認証ユーザーの全イベントを取得
- `lastExecutedHistory` リレーションをEager Loadingで取得（N+1問題を回避）
- 作成日時の昇順でソート
- レスポンスをJSON形式に整形

### 2. ルート定義

**ファイル**: `routes/api.php`

```php
Route::middleware('auth:api')->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
});
```

- エンドポイント: `GET /events`
- 認証必須（JWT Bearer Token）
- ルート名: `events.index`

### 3. テストの作成

**ファイル**: `tests/Feature/EventIndexTest.php`

以下のテストケースを実装：

1. **認証ユーザーの全イベントを取得**
   - 複数のイベントと履歴を作成
   - 他ユーザーのイベントは含まれないことを確認
   - レスポンス構造とデータの検証

2. **イベントが0件の場合**
   - 空の配列が返されることを確認

3. **未認証の場合**
   - 401エラーが返されることを確認

4. **履歴のないイベント**
   - `lastExecutedHistoryId`, `lastExecutedAt`, `lastExecutedMemo` が null であることを確認

5. **ISO 8601形式の日時**
   - 日時が ISO 8601 形式で返されることを確認

## APIエンドポイント

### GET /events

認証ユーザーの全イベントを取得します。

#### リクエスト

```http
GET /events
Authorization: Bearer {access_token}
```

#### レスポンス（成功時: 200 OK）

```json
{
  "success": true,
  "data": {
    "events": [
      {
        "id": "evt_1234567890",
        "userId": 1,
        "name": "エアコンフィルター掃除",
        "categoryIcon": "leaf",
        "lastExecutedHistoryId": "hist_1234567890",
        "lastExecutedAt": "2026-01-15T23:31:00+09:00",
        "lastExecutedMemo": "フィルターを水洗いした",
        "createdAt": "2023-10-15T14:00:00+09:00",
        "updatedAt": "2026-01-15T23:31:00+09:00"
      }
    ]
  },
  "meta": {
    "timestamp": "2026-01-27T12:34:56+09:00"
  }
}
```

#### レスポンス（認証エラー: 401 Unauthorized）

```json
{
  "message": "Unauthenticated."
}
```

## データフロー

```
[クライアント]
    ↓ GET /events (Bearer Token)
[ルーティング] (routes/api.php)
    ↓ auth:api middleware
[EventController::index]
    ↓ Event::where('user_id', $user->id)->with('lastExecutedHistory')->get()
[データベース]
    ↓ events テーブル + histories テーブル (JOIN)
[EventController::index]
    ↓ データ整形 (camelCase変換、ISO 8601形式)
[JSONレスポンス]
    ↓
[クライアント]
```

## 特徴

### 1. Eager Loading

N+1問題を回避するため、`with('lastExecutedHistory')` でリレーションを一度に取得しています。

```php
$events = Event::where('user_id', $user->id)
    ->with('lastExecutedHistory')
    ->orderBy('created_at', 'asc')
    ->get();
```

### 2. データ整形

APIレスポンスではキャメルケースを使用するため、スネークケースのデータベースカラムを変換しています。

```php
return [
    'id' => $event->id,
    'userId' => $event->user_id,
    'name' => $event->name,
    'categoryIcon' => $event->category_icon,
    'lastExecutedHistoryId' => $event->last_executed_history_id,
    'lastExecutedAt' => $lastHistory?->executed_at?->toIso8601String(),
    'lastExecutedMemo' => $lastHistory?->memo,
    'createdAt' => $event->created_at->toIso8601String(),
    'updatedAt' => $event->updated_at->toIso8601String(),
];
```

### 3. ISO 8601形式の日時

日時は ISO 8601 形式（例: `2026-01-27T12:34:56+09:00`）で返却します。

```php
$lastHistory?->executed_at?->toIso8601String()
```

### 4. Null Safe Operator

履歴が存在しない場合に備えて、Null Safe Operator (`?->`) を使用しています。

```php
$lastHistory?->executed_at?->toIso8601String()
```

## テスト実行

```bash
# 全テスト実行
docker compose exec php php artisan test

# EventIndexTest のみ実行
docker compose exec php php artisan test --filter=EventIndexTest
```

## コード品質チェック

```bash
# Laravel Pint (コードフォーマット)
docker compose exec php composer lint

# PHPStan (静的解析)
docker compose exec php composer analyse
```

## 次のステップ

Phase 1のMVPとして、以下の機能も実装予定です：

- [ ] イベント詳細取得 (`GET /events/:id`)
- [ ] イベント作成 (`POST /events`)
- [ ] イベント更新 (`PUT /events/:id`)
- [ ] イベント削除 (`DELETE /events/:id`)
- [ ] 履歴一覧取得 (`GET /events/:id/history`)
- [ ] 履歴追加 (`POST /events/:id/history`)
- [ ] 履歴更新 (`PUT /events/:id/history/:historyId`)
- [ ] 履歴削除 (`DELETE /events/:id/history/:historyId`)

## 参考資料

- [API設計書](../doc/design_spec/API.md)
- [メイン画面設計書](../doc/design_spec/メイン画面（イベント一覧）.md)

---

**作成日**: 2026/01/27  
**作成者**: GitHub Copilot
