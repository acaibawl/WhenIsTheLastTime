<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\JWTGuard;

class SocialAuthController extends Controller
{
    /**
     * 対応しているソーシャルプロバイダー
     *
     * @var list<string>
     */
    private const array SUPPORTED_PROVIDERS = ['twitter'];

    /**
     * ソーシャル認証プロバイダーへリダイレクト
     */
    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNSUPPORTED_PROVIDER',
                    'message' => '対応していない認証プロバイダーです',
                ],
            ], 400);
        }

        /** @var \Laravel\Socialite\Two\AbstractProvider|\SocialiteProviders\Manager\OAuth1\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->redirect();
    }

    /**
     * ソーシャル認証コールバック処理
     *
     * Twitter認証後、ユーザーを作成またはログインさせ、
     * フロントエンドにJWTトークンを渡してリダイレクトする
     */
    public function callback(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNSUPPORTED_PROVIDER',
                    'message' => '対応していない認証プロバイダーです',
                ],
            ], 400);
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider|\SocialiteProviders\Manager\OAuth1\AbstractProvider $driver */
            $driver = Socialite::driver($provider);

            /** @var \Laravel\Socialite\AbstractUser $socialUser */
            $socialUser = $driver->user();

            $providerIdColumn = "{$provider}_id";
            $providerId = $socialUser->getId();

            if (empty($providerId)) {
                Log::warning('Social auth: empty provider ID', ['provider' => $provider]);

                return redirect()->to("{$frontendUrl}/login?error=social_auth_failed");
            }

            DB::beginTransaction();
            try {
                // 既存ユーザー検索（Twitter IDで検索）
                /** @var User|null $user */
                $user = User::where($providerIdColumn, (string) $providerId)->first();

                if ($user) {
                    // 既存ユーザー: そのままログイン
                    DB::commit();
                } else {
                    // 新規ユーザー作成
                    $nickname = $socialUser->getNickname() ?: $socialUser->getName() ?: 'ユーザー';
                    // ニックネームを10文字以内に切り詰め
                    $nickname = mb_substr($nickname, 0, 10);

                    // メールアドレスの取得（Twitterはメールを返さない場合がある）
                    $email = $socialUser->getEmail();
                    if (empty($email)) {
                        // メールがない場合、仮のメールアドレスを生成
                        $email = "twitter_{$providerId}@social.witlt.local";
                    }

                    // 同じメールアドレスの既存ユーザーがいないか確認
                    $existingUserByEmail = User::where('email', $email)->first();
                    if ($existingUserByEmail) {
                        // 既存のメールユーザーにTwitter IDを紐付け
                        $existingUserByEmail->update([$providerIdColumn => (string) $providerId]);
                        $user = $existingUserByEmail;
                    } else {
                        /** @var User $user */
                        $user = User::create([
                            'email' => $email,
                            $providerIdColumn => (string) $providerId,
                            'password_hash' => Str::random(32),
                            'nickname' => $nickname,
                        ]);

                        // デフォルト設定を作成
                        UserSetting::create([
                            'user_id' => $user->id,
                            'settings_json' => UserSetting::getDefaultSettings(),
                        ]);
                    }

                    DB::commit();
                }

                // JWT発行
                /** @var JWTGuard $guard */
                $guard = auth()->guard('api');
                /** @var string $token */
                $token = $guard->login($user);

                // フロントエンドのコールバックページにトークンを渡してリダイレクト
                return redirect()->to("{$frontendUrl}/auth/social/callback?token={$token}&provider={$provider}");
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            Log::error('Social authentication failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to("{$frontendUrl}/login?error=social_auth_failed");
        }
    }
}
