<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPost;
use App\Http\Requests\Auth\ResendCodePost;
use App\Http\Requests\Auth\SendCodePost;
use App\Http\Requests\Auth\VerifyCodePost;
use App\Mail\Auth\RegistrationAttemptNotification;
use App\Mail\Auth\VerificationCodeMail;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * 認証コード送信API
     */
    public function sendVerificationCode(SendCodePost $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $password = $validated['password'];
        $nickname = $validated['nickname'];

        $ttl = (int) config('auth.verification_code_ttl', 600);
        $maxAttempts = (int) config('auth.verification_code_max_attempts', 5);

        // 1. レート制限チェック（メールアドレス単位）
        $sendCount = Redis::get("rate_limit:registration:{$email}");
        if ($sendCount && (int) $sendCount >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => '送信回数の上限に達しました。しばらくしてからお試しください',
                ],
            ], 429);
        }

        // 2. メール重複チェック
        $isExistingUser = User::where('email', $email)->exists();

        // 3. 6桁コード生成（どちらのケースでも生成）
        $code = sprintf('%06d', random_int(0, 999999));

        if ($isExistingUser) {
            // 4a. 既存ユーザーの場合：ダミーデータをRedisに保存
            $data = [
                'email' => $email,
                'passwordHash' => '',
                'nickname' => '',
                'code' => Hash::make($code),
                'attempts' => 0,
                'lastSentAt' => time(),
                'createdAt' => time(),
                'isExistingUser' => true,
            ];
            Redis::setex("registration:{$email}", $ttl, json_encode($data));

            // 5a. 既存ユーザーには通知メールを送信
            Mail::to($email)->send(new RegistrationAttemptNotification());
        } else {
            // 4b. 新規ユーザーの場合：正規の登録データを保存
            $data = [
                'email' => $email,
                'passwordHash' => Hash::make($password),
                'nickname' => $nickname,
                'code' => Hash::make($code),
                'attempts' => 0,
                'lastSentAt' => time(),
                'createdAt' => time(),
                'isExistingUser' => false,
            ];
            Redis::setex("registration:{$email}", $ttl, json_encode($data));

            // 5b. 認証コードメール送信
            Mail::to($email)->send(new VerificationCodeMail($code));
        }

        // 6. レート制限カウンター更新
        Redis::incr("rate_limit:registration:{$email}");
        Redis::expire("rate_limit:registration:{$email}", 3600);

        // 7. 同一のレスポンスを返す（ユーザー列挙攻撃対策）
        return response()->json([
            'success' => true,
            'data' => [
                'message' => '認証コードを送信しました',
                'email' => $email,
                'expiresIn' => $ttl,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * 認証コード検証・登録完了API
     */
    public function verifyRegistrationCode(VerifyCodePost $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];

        // 1. 登録データ取得
        $data = Redis::get("registration:{$email}");
        if (! $data) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_VERIFICATION_CODE',
                    'message' => '認証コードが正しくありません',
                ],
            ], 400);
        }

        /** @var array<string, mixed> $registrationData */
        $registrationData = json_decode($data, true);

        $maxAttempts = (int) config('auth.verification_code_max_attempts', 5);

        // 2. 試行回数チェック
        if ($registrationData['attempts'] >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOO_MANY_ATTEMPTS',
                    'message' => '試行回数が上限に達しました。しばらくしてからお試しください',
                ],
            ], 429);
        }

        // 3. コード検証
        if (! Hash::check($code, $registrationData['code'])) {
            // 試行回数をインクリメント
            $registrationData['attempts']++;
            $ttl = Redis::ttl("registration:{$email}");
            Redis::setex(
                "registration:{$email}",
                $ttl > 0 ? $ttl : 600,
                json_encode($registrationData)
            );

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_VERIFICATION_CODE',
                    'message' => '認証コードが正しくありません',
                ],
            ], 400);
        }

        // 4. 既存ユーザーフラグチェック
        if ($registrationData['isExistingUser']) {
            Redis::del("registration:{$email}");

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_VERIFICATION_CODE',
                    'message' => '認証コードが正しくありません',
                ],
            ], 400);
        }

        // 5. ユーザー作成
        $user = User::create([
            'email' => $registrationData['email'],
            'password_hash' => $registrationData['passwordHash'],
            'nickname' => $registrationData['nickname'],
        ]);

        // 5-1. デフォルト設定を作成
        UserSetting::create([
            'user_id' => $user->id,
            'settings_json' => UserSetting::getDefaultSettings(),
        ]);

        // 6. Redis削除
        Redis::del("registration:{$email}");

        // 7. JWT発行
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');
        /** @var mixed $token */
        $token = $guard->login($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'createdAt' => $user->created_at?->toIso8601String(),
                ],
                'accessToken' => $token,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * 認証コード再送信API
     */
    public function resendVerificationCode(ResendCodePost $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        // 1. 登録データ取得
        $data = Redis::get("registration:{$email}");
        if (! $data) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_VERIFICATION_CODE',
                    'message' => '認証コードが正しくありません',
                ],
            ], 400);
        }

        /** @var array<string, mixed> $registrationData */
        $registrationData = json_decode($data, true);

        // 2. クールダウンチェック
        $cooldownSeconds = (int) config('auth.verification_code_resend_cooldown', 60);
        $timeSinceLastSent = time() - $registrationData['lastSentAt'];
        if ($timeSinceLastSent < $cooldownSeconds) {
            $retryAfter = $cooldownSeconds - $timeSinceLastSent;

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESEND_COOLDOWN',
                    'message' => '再送信は60秒後に可能です',
                    'details' => [
                        'retryAfter' => $retryAfter,
                    ],
                ],
            ], 429);
        }

        // 3. 新しいコード生成
        $code = sprintf('%06d', random_int(0, 999999));
        $registrationData['code'] = Hash::make($code);
        $registrationData['lastSentAt'] = time();
        $registrationData['attempts'] = 0; // 試行回数をリセット

        // 4. Redisに保存
        $ttl = Redis::ttl("registration:{$email}");
        Redis::setex(
            "registration:{$email}",
            $ttl > 0 ? $ttl : 600,
            json_encode($registrationData)
        );

        // 5. メール送信
        if ($registrationData['isExistingUser']) {
            Mail::to($email)->send(new RegistrationAttemptNotification());
        } else {
            Mail::to($email)->send(new VerificationCodeMail($code));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => '認証コードを再送信しました',
                'email' => $email,
                'expiresIn' => $ttl > 0 ? $ttl : 600,
            ],
        ]);
    }

    public function login(LoginPost $request): JsonResponse
    {
        $credentials = $request->validated();
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');
        // email・password（自動でハッシュする）で検索をかけて、一致するuserがいればtokenを設定。なければfalseが入る
        /** @var mixed $token */
        $token = $guard->attempt($credentials);
        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_ERROR',
                    'message' => 'メールアドレスまたはパスワードが正しくありません',
                ],
            ], 401);
        }

        $user = $guard->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'createdAt' => $user->created_at?->toIso8601String(),
                ],
                'accessToken' => $token,
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth()->guard('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'createdAt' => $user->created_at?->toIso8601String(),
                    'updatedAt' => $user->updated_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');
        $guard->logout();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'ログアウトしました',
            ],
        ]);
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');
        $token = $guard->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'accessToken' => $token,
                'refreshToken' => $token,
                'expiresIn' => config('jwt.ttl') * 60,
            ],
        ]);
    }
}
