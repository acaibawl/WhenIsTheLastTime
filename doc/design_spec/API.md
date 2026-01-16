# API設計書

## 1. 概要

### 1.1 APIの目的
「最後はいつ？ (When Is The Last Time)」アプリケーションのバックエンドAPIの仕様を定義する。

### 1.2 ベースURL
```
本番環境: https://api.example.com
開発環境: https://api-dev.example.com
ローカル: http://localhost:3001
```

### 1.3 API設計方針
- **RESTful API**: リソース指向の設計
- **バージョン管理なし**: URLにバージョン番号を含めない
- **JSON形式**: リクエスト・レスポンスともにJSON
- **HTTPメソッド**: GET（取得）、POST（作成）、PUT/PATCH（更新）、DELETE（削除）
- **ステータスコード**: 標準的なHTTPステータスコードを使用

### 1.4 認証方式
- **JWT (JSON Web Token)**: Bearer Token認証
- **Cookie**: HttpOnly、Secure、SameSite=Strict
- **セッションタイムアウト**: 7日間

## 2. 共通仕様

### 2.1 リクエストヘッダー

#### 必須ヘッダー
```http
Content-Type: application/json
Accept: application/json
```

#### 認証が必要なエンドポイント
```http
Authorization: Bearer {access_token}
```

#### その他のヘッダー
```http
X-Request-ID: {uuid}  # リクエスト追跡用（任意）
Accept-Language: ja   # 言語設定（任意、デフォルト: ja）
```

### 2.2 レスポンス形式

#### 成功時（200, 201など）

```typescript
interface SuccessResponse<T> {
  success: true;
  data: T;
  meta?: {
    timestamp: string;    // ISO 8601形式
    requestId?: string;   // リクエストID
  };
}
```

**例:**
```json
{
  "success": true,
  "data": {
    "events": [...]
  },
  "meta": {
    "timestamp": "2026-01-16T12:34:56Z",
    "requestId": "req_1234567890"
  }
}
```

#### エラー時（400, 500など）

```typescript
interface ErrorResponse {
  success: false;
  error: {
    code: string;         // エラーコード
    message: string;      // エラーメッセージ（ユーザー向け）
    details?: any;        // 詳細情報（開発環境のみ）
  };
  meta?: {
    timestamp: string;
    requestId?: string;
  };
}
```

**例:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {
      "name": "イベント名は必須です"
    }
  },
  "meta": {
    "timestamp": "2026-01-16T12:34:56Z",
    "requestId": "req_1234567890"
  }
}
```

### 2.3 HTTPステータスコード

| コード | 説明 | 使用例 |
|-------|------|--------|
| 200 | OK | 取得・更新成功 |
| 201 | Created | 作成成功 |
| 204 | No Content | 削除成功（レスポンスボディなし） |
| 400 | Bad Request | バリデーションエラー |
| 401 | Unauthorized | 認証が必要 |
| 403 | Forbidden | 権限不足 |
| 404 | Not Found | リソースが見つからない |
| 409 | Conflict | リソースの競合 |
| 422 | Unprocessable Entity | 処理できないエンティティ |
| 429 | Too Many Requests | レート制限超過 |
| 500 | Internal Server Error | サーバー内部エラー |
| 503 | Service Unavailable | サービス利用不可 |

### 2.4 エラーコード一覧

| コード | 説明 |
|-------|------|
| VALIDATION_ERROR | バリデーションエラー |
| AUTHENTICATION_ERROR | 認証エラー |
| AUTHORIZATION_ERROR | 認可エラー |
| NOT_FOUND | リソースが見つからない |
| CONFLICT | リソースの競合 |
| RATE_LIMIT_EXCEEDED | レート制限超過 |
| INTERNAL_ERROR | サーバー内部エラー |
| SERVICE_UNAVAILABLE | サービス利用不可 |
| INVALID_TOKEN | 無効なトークン |
| EXPIRED_TOKEN | トークンの期限切れ |

### 2.5 ページネーション

現在はページネーションなし（全件取得）。
Phase 2以降で実装予定：

```
GET /events?page=1&per_page=20
```

```json
{
  "success": true,
  "data": {
    "events": [...],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    }
  }
}
```

### 2.6 レート制限

- **制限**: 1000リクエスト/時間/ユーザー
- **レスポンスヘッダー**:
  ```http
  X-RateLimit-Limit: 1000
  X-RateLimit-Remaining: 999
  X-RateLimit-Reset: 1642329600
  ```

- **超過時**: 429 Too Many Requests

## 3. 認証API

### 3.1 ユーザー登録

#### エンドポイント
```
POST /auth/register
```

#### リクエスト

```typescript
interface RegisterRequest {
  email: string;         // メールアドレス
  password: string;      // パスワード（8文字以上）
  nickname: string;      // ニックネーム（1〜10文字）
}
```

**リクエスト例:**
```json
POST /auth/register

{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "nickname": "Taro"
}
```

#### レスポンス

**成功時（201 Created）:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "nickname": "Taro",
      "createdAt": "2026-01-16T12:34:56Z"
    },
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**エラー時（400 Bad Request）:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {
      "email": "このメールアドレスは既に使用されています"
    }
  }
}
```

### 3.2 ログイン

#### エンドポイント
```
POST /auth/login
```

#### リクエスト

```typescript
interface LoginRequest {
  email: string;
  password: string;
}
```

**リクエスト例:**
```json
POST /auth/login

{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "nickname": "Taro",
      "createdAt": "2026-01-16T12:34:56Z"
    },
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**エラー時（401 Unauthorized）:**
```json
{
  "success": false,
  "error": {
    "code": "AUTHENTICATION_ERROR",
    "message": "メールアドレスまたはパスワードが正しくありません"
  }
}
```

### 3.3 ログアウト

#### エンドポイント
```
POST /auth/logout
```

#### リクエスト

ヘッダーのみ（ボディなし）

**リクエスト例:**
```http
POST /auth/logout
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "message": "ログアウトしました"
  }
}
```

### 3.4 現在のユーザー情報取得

#### エンドポイント
```
GET /auth/me
```

#### リクエスト

ヘッダーのみ（ボディなし）

**リクエスト例:**
```http
GET /auth/me
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "nickname": "Taro",
      "createdAt": "2026-01-16T12:34:56Z",
      "updatedAt": "2026-01-16T12:34:56Z"
    }
  }
}
```

**エラー時（401 Unauthorized）:**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_TOKEN",
    "message": "認証トークンが無効です"
  }
}
```

## 4. イベントAPI

### 4.1 イベント一覧取得

#### エンドポイント
```
GET /events
```

#### 認証
必須

#### リクエスト

クエリパラメータなし（全件取得）

**リクエスト例:**
```http
GET /events
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```typescript
interface GetEventsResponse {
  success: true;
  data: {
    events: Event[];
  };
}

interface Event {
  id: string;
  userId: number;
  name: string;
  categoryIcon: string;
  lastExecutedAt: string;
  createdAt: string;
  updatedAt: string;
}
```

**レスポンス例:**
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
        "lastExecutedAt": "2026-01-15T23:31:00Z",
        "createdAt": "2023-10-15T14:00:00Z",
        "updatedAt": "2026-01-15T23:31:00Z"
      },
      {
        "id": "evt_0987654321",
        "userId": 1,
        "name": "運転免許更新",
        "categoryIcon": "folder",
        "lastExecutedAt": "2023-10-15T14:00:00Z",
        "createdAt": "2023-10-15T14:00:00Z",
        "updatedAt": "2023-10-15T14:00:00Z"
      }
    ]
  }
}
```

### 4.2 イベント詳細取得

#### エンドポイント
```
GET /events/:id
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID

#### リクエスト例
```http
GET /events/evt_1234567890
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "event": {
      "id": "evt_1234567890",
      "userId": 1,
      "name": "エアコンフィルター掃除",
      "categoryIcon": "leaf",
      "lastExecutedAt": "2026-01-15T23:31:00Z",
      "createdAt": "2023-10-15T14:00:00Z",
      "updatedAt": "2026-01-15T23:31:00Z"
    }
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Event not found"
  }
}
```

### 4.3 イベント作成

#### エンドポイント
```
POST /events
```

#### 認証
必須

#### リクエスト

```typescript
interface CreateEventRequest {
  name: string;           // イベント名（1〜100文字、必須）
  categoryIcon: string;   // カテゴリーアイコン（必須）
  lastExecutedAt: string; // 最終実行日時（ISO 8601形式、必須）
}
```

**リクエスト例:**
```json
POST /events
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "name": "エアコンフィルター掃除",
  "categoryIcon": "leaf",
  "lastExecutedAt": "2026-01-15T23:31:00Z"
}
```

#### レスポンス

**成功時（201 Created）:**
```json
{
  "success": true,
  "data": {
    "event": {
      "id": "evt_1234567890",
      "userId": 1,
      "name": "エアコンフィルター掃除",
      "categoryIcon": "leaf",
      "lastExecutedAt": "2026-01-15T23:31:00Z",
      "createdAt": "2026-01-16T12:34:56Z",
      "updatedAt": "2026-01-16T12:34:56Z"
    }
  }
}
```

**エラー時（400 Bad Request）:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {
      "name": "イベント名は必須です",
      "categoryIcon": "無効なカテゴリーアイコンです"
    }
  }
}
```

#### バリデーション

- **name**:
  - 必須
  - 1〜100文字
  - 空白のみ不可

- **categoryIcon**:
  - 必須
  - 以下のいずれか: pin, book, folder, star, chart, sun, person, hospital, medical, leaf, search, people, snowflake, fire, lightning

- **lastExecutedAt**:
  - 必須
  - ISO 8601形式
  - 未来の日時は不可

### 4.4 イベント更新

#### エンドポイント
```
PUT /events/:id
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID

#### リクエスト

```typescript
interface UpdateEventRequest {
  name: string;           // イベント名（1〜100文字、必須）
  categoryIcon: string;   // カテゴリーアイコン（必須）
  lastExecutedAt: string; // 最終実行日時（ISO 8601形式、必須）
}
```

**リクエスト例:**
```json
PUT /events/evt_1234567890
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "name": "エアコンフィルター掃除（更新）",
  "categoryIcon": "leaf",
  "lastExecutedAt": "2026-01-16T10:00:00Z"
}
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "event": {
      "id": "evt_1234567890",
      "userId": 1,
      "name": "エアコンフィルター掃除（更新）",
      "categoryIcon": "leaf",
      "lastExecutedAt": "2026-01-16T10:00:00Z",
      "createdAt": "2023-10-15T14:00:00Z",
      "updatedAt": "2026-01-16T12:40:00Z"
    }
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Event not found"
  }
}
```

### 4.5 イベント削除

#### エンドポイント
```
DELETE /events/:id
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID

#### リクエスト例
```http
DELETE /events/evt_1234567890
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "message": "Event deleted successfully"
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Event not found"
  }
}
```

**注意:**
- イベントを削除すると、関連するすべての履歴も削除される（CASCADE）

## 5. 履歴API

### 5.1 履歴一覧取得

#### エンドポイント
```
GET /events/:id/history
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID

#### リクエスト例
```http
GET /events/evt_1234567890/history
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```typescript
interface GetHistoriesResponse {
  success: true;
  data: {
    histories: History[];
  };
}

interface History {
  id: string;
  eventId: string;
  executedAt: string;
  memo?: string;
  createdAt: string;
  updatedAt: string;
}
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "histories": [
      {
        "id": "hist_1111111111",
        "eventId": "evt_1234567890",
        "executedAt": "2026-01-15T23:31:00Z",
        "memo": "フィルターを水洗いした",
        "createdAt": "2026-01-15T23:31:00Z",
        "updatedAt": "2026-01-15T23:31:00Z"
      },
      {
        "id": "hist_2222222222",
        "eventId": "evt_1234567890",
        "executedAt": "2023-10-01T10:00:00Z",
        "memo": "前回の掃除",
        "createdAt": "2023-10-01T10:00:00Z",
        "updatedAt": "2023-10-01T10:00:00Z"
      }
    ]
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Event not found"
  }
}
```

### 5.2 履歴追加

#### エンドポイント
```
POST /events/:id/history
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID

#### リクエスト

```typescript
interface CreateHistoryRequest {
  executedAt: string;  // 実行日時（ISO 8601形式、必須）
  memo?: string;       // メモ（任意、最大500文字）
}
```

**リクエスト例:**
```json
POST /events/evt_1234567890/history
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "executedAt": "2026-01-16T15:30:00Z",
  "memo": "今回は念入りに掃除した"
}
```

#### レスポンス

**成功時（201 Created）:**
```json
{
  "success": true,
  "data": {
    "history": {
      "id": "hist_4444444444",
      "eventId": "evt_1234567890",
      "executedAt": "2026-01-16T15:30:00Z",
      "memo": "今回は念入りに掃除した",
      "createdAt": "2026-01-16T15:30:00Z",
      "updatedAt": "2026-01-16T15:30:00Z"
    }
  }
}
```

**エラー時（400 Bad Request）:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {
      "executedAt": "実行日時は必須です"
    }
  }
}
```

**注意:**
- 履歴エントリを追加すると、親イベントの `lastExecutedAt` が自動的に更新される

### 5.3 履歴更新

#### エンドポイント
```
PUT /events/:id/history/:historyId
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID
- `historyId`: 履歴ID

#### リクエスト

```typescript
interface UpdateHistoryRequest {
  executedAt: string;  // 実行日時（ISO 8601形式、必須）
  memo?: string;       // メモ（任意、最大500文字）
}
```

**リクエスト例:**
```json
PUT /events/evt_1234567890/history/hist_1111111111
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "executedAt": "2026-01-15T23:31:00Z",
  "memo": "フィルターを水洗いし、乾燥させた"
}
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "history": {
      "id": "hist_1111111111",
      "eventId": "evt_1234567890",
      "executedAt": "2026-01-15T23:31:00Z",
      "memo": "フィルターを水洗いし、乾燥させた",
      "createdAt": "2026-01-15T23:31:00Z",
      "updatedAt": "2026-01-16T10:00:00Z"
    }
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "History not found"
  }
}
```

**注意:**
- 履歴エントリを更新すると、親イベントの `lastExecutedAt` も再計算される

### 5.4 履歴削除

#### エンドポイント
```
DELETE /events/:id/history/:historyId
```

#### 認証
必須

#### パスパラメータ
- `id`: イベントID
- `historyId`: 履歴ID

#### リクエスト例
```http
DELETE /events/evt_1234567890/history/hist_1111111111
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "message": "History deleted successfully"
  }
}
```

**エラー時（404 Not Found）:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "History not found"
  }
}
```

**注意:**
- 履歴エントリを削除すると、親イベントの `lastExecutedAt` も再計算される

## 6. 設定API

### 6.1 設定取得

#### エンドポイント
```
GET /settings
```

#### 認証
必須

#### リクエスト例
```http
GET /settings
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
```typescript
interface GetSettingsResponse {
  success: true;
  data: {
    settings: ServerSettings;
  };
}

interface ServerSettings {
  export: {
    lastExportedAt?: string;
  };
  notification: {
    reminder: ReminderSettings;
  };
  misc: {
    showTutorial: boolean;
  };
}

interface ReminderSettings {
  enabled: boolean;
  timing: {
    type: 'daily' | 'weekly' | 'monthly';
    time: string;
    dayOfWeek?: number;
    dayOfMonth?: number;
  };
  targetEvents: 'all' | 'week' | 'month' | 'year';
}
```

**レスポンス例:**
```json
{
  "success": true,
  "data": {
    "settings": {
      "export": {
        "lastExportedAt": "2026-01-15T10:00:00Z"
      },
      "notification": {
        "reminder": {
          "enabled": false,
          "timing": {
            "type": "daily",
            "time": "09:00"
          },
          "targetEvents": "week"
        }
      },
      "misc": {
        "showTutorial": false
      }
    }
  }
}
```

**注意:**
- ソート順、時間設定、タイムフリッパーはローカルストレージで管理されるため、このAPIには含まれない

### 6.2 設定更新

#### エンドポイント
```
PATCH /settings
```

#### 認証
必須

#### リクエスト

```typescript
interface UpdateSettingsRequest {
  export?: {
    lastExportedAt?: string;
  };
  notification?: {
    reminder?: ReminderSettings;
  };
  misc?: {
    showTutorial?: boolean;
  };
}
```

**リクエスト例:**
```json
PATCH /settings
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "misc": {
    "showTutorial": true
  }
}
```

#### レスポンス

**成功時（200 OK）:**
```json
{
  "success": true,
  "data": {
    "settings": {
      "export": {},
      "notification": {
        "reminder": {
          "enabled": false,
          "timing": {
            "type": "daily",
            "time": "09:00"
          },
          "targetEvents": "week"
        }
      },
      "misc": {
        "showTutorial": true
      }
    }
  }
}
```

**エラー時（400 Bad Request）:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります"
  }
}
```

## 7. エクスポートAPI

### 7.1 CSVエクスポート

#### エンドポイント
```
GET /export/csv
```

#### 認証
必須

#### リクエスト例
```http
GET /export/csv
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### レスポンス

**成功時（200 OK）:**
- Content-Type: `text/csv; charset=utf-8`
- Content-Disposition: `attachment; filename="when-is-the-last-time_20260116_123456.csv"`

**CSVフォーマット:**
```csv
イベントID,イベント名,カテゴリーアイコン,作成日時,最終実行日時,履歴ID,履歴実行日時,履歴メモ
evt_001,エアコンフィルター掃除,leaf,2023-10-15T14:00:00Z,2026-01-15T23:31:00Z,hist_001,2026-01-15T23:31:00Z,フィルターを水洗いした
evt_001,エアコンフィルター掃除,leaf,2023-10-15T14:00:00Z,2026-01-15T23:31:00Z,hist_002,2023-10-01T10:00:00Z,前回の掃除
evt_002,運転免許更新,folder,2023-10-15T14:00:00Z,2023-10-15T14:00:00Z,hist_003,2023-10-15T14:00:00Z,初回記録
```

**エラー時（500 Internal Server Error）:**
```json
{
  "success": false,
  "error": {
    "code": "EXPORT_FAILED",
    "message": "Failed to export data"
  }
}
```

## 8. API実装ガイドライン

### 8.1 セキュリティ

#### 認証・認可
- すべてのAPIエンドポイント（認証API除く）で認証を必須とする
- JWTの有効期限を7日間に設定
- リフレッシュトークンは30日間有効

#### CORS設定
```javascript
// 本番環境
Access-Control-Allow-Origin: https://example.com
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Credentials: true

// 開発環境
Access-Control-Allow-Origin: http://localhost:3000
```

#### SQL Injection対策
- プリペアドステートメントを使用
- ORMの活用（TypeORM、Prismaなど）

#### XSS対策
- ユーザー入力のサニタイズ
- Content-Security-Policyヘッダーの設定

### 8.2 パフォーマンス

#### キャッシュ戦略
- イベント一覧: 5分間キャッシュ（Redis）
- 設定情報: 1時間キャッシュ

#### データベース最適化
- 適切なインデックスの設定
- N+1問題の回避
- コネクションプーリング

### 8.3 ログとモニタリング

#### ログ出力
```javascript
{
  timestamp: "2026-01-16T12:34:56Z",
  level: "info",
  method: "GET",
  path: "/events",
  statusCode: 200,
  duration: 45,
  userId: 1,
  requestId: "req_1234567890",
  userAgent: "Mozilla/5.0...",
  ip: "192.168.1.1"
}
```

#### メトリクス
- リクエスト数/秒
- レスポンス時間（平均、中央値、99パーセンタイル）
- エラー率
- アクティブユーザー数

### 8.4 エラーハンドリング

#### グローバルエラーハンドラー

```typescript
app.use((err: Error, req: Request, res: Response, next: NextFunction) => {
  const statusCode = err.statusCode || 500;
  const errorCode = err.code || 'INTERNAL_ERROR';
  
  // ログ出力
  logger.error({
    error: err.message,
    stack: err.stack,
    requestId: req.id,
    userId: req.user?.id,
  });
  
  // レスポンス
  res.status(statusCode).json({
    success: false,
    error: {
      code: errorCode,
      message: getErrorMessage(errorCode),
      details: process.env.NODE_ENV === 'development' ? err.stack : undefined,
    },
    meta: {
      timestamp: new Date().toISOString(),
      requestId: req.id,
    },
  });
});
```

### 8.5 テスト

#### 単体テスト
- カバレッジ: 80%以上
- テストフレームワーク: Jest、Vitest

#### 統合テスト
- APIエンドポイントごとにテスト
- 正常系・異常系の両方をカバー

#### E2Eテスト
- 主要なユースケースをテスト
- ツール: Playwright、Cypress

## 9. API変更履歴

### v1.0.0（2026/01/16）
- 初版リリース
- 認証API追加
- イベントAPI追加
- 履歴API追加
- 設定API追加
- エクスポートAPI追加

---

**作成日**: 2026/01/16  
**バージョン**: 1.0  
**更新履歴**:
- 2026/01/16: 初版作成
