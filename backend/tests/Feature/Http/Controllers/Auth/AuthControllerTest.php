<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 認証周りのテスト
 */
class AuthControllerTest extends TestCase
{
    /**
     * ログイン成功のテスト
     */
    #[Test]
    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'password' => \Hash::make('password'),
        ]);

        $requestBody = [
            'email' => $user->email,
            'password' => 'password',
        ];
        $response = $this->postJson('/auth/login', $requestBody);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json->hasAll(
            [
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->where('token_type', 'bearer')
        );
    }

    /**
     * ログインリクエストのパラメータバリデーションエラーのテスト
     */
    #[Test]
    #[DataProvider('dataProviderLoginInvalidParameter')]
    public function test_login_failed_by_validation_error(array $requestBody, string $errorMessage): void
    {
        $response = $this->postJson('/auth/login', $requestBody);
        $response->assertUnprocessable();
        $response->assertJson(fn (AssertableJson $json) => $json->where('message', $errorMessage)
            ->etc()
        );
    }

    /**
     * @return array[]
     */
    public static function dataProviderLoginInvalidParameter(): array
    {
        return [
            'email空文字' => [
                'requestBody' => [
                    'email' => '',
                    'password' => 'password',
                ],
                'errorMessage' => 'メールアドレスは必須項目です。',
            ],
            'emailフォーマット誤り' => [
                'requestBody' => [
                    'email' => 'acai',
                    'password' => 'password',
                ],
                'errorMessage' => 'メールアドレスは、有効なメールアドレス形式で指定してください。',
            ],
            'email文字数超過' => [
                'requestBody' => [
                    'email' => str_repeat('a', 244) . '@example.com',
                    'password' => 'password',
                ],
                'errorMessage' => 'メールアドレスの文字数は、255文字以下である必要があります。',
            ],
            'password空文字' => [
                'requestBody' => [
                    'email' => 'acai@example.com',
                    'password' => '',
                ],
                'errorMessage' => 'パスワードは必須項目です。',
            ],
            'password文字数不足' => [
                'requestBody' => [
                    'email' => 'acai@example.com',
                    'password' => str_repeat('a', 7),
                ],
                'errorMessage' => 'パスワードは、8文字から32文字にしてください。',
            ],
            'password文字数超過' => [
                'requestBody' => [
                    'email' => 'acai@example.com',
                    'password' => str_repeat('a', 33),
                ],
                'errorMessage' => 'パスワードは、8文字から32文字にしてください。',
            ],
        ];
    }

    /**
     * 認証情報誤りによるログイン失敗のテスト
     */
    #[Test]
    #[DataProvider('dataProviderLoginMismatch')]
    public function test_login_failed_by_mismatch_of_authentication_information(array $requestBody): void
    {
        User::factory()->create([
            'email' => 'acai@example.com',
            'password' => \Hash::make('password'),
        ]);
        $response = $this->postJson('/auth/login', $requestBody);
        $response->assertUnauthorized();
    }

    /**
     * @return array[]
     */
    public static function dataProviderLoginMismatch(): array
    {
        return [
            'email不一致' => [
                'requestBody' => [
                    'email' => 'mismatch@example.com',
                    'password' => 'password',
                ],
            ],
            'password不一致' => [
                'requestBody' => [
                    'email' => 'acai@example.com',
                    'password' => 'mismatch',
                ],
            ],
        ];
    }

    /**
     * ログインしていればmeにアクセスできることのテスト
     */
    public function test_me_can_access_logged_in(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->getJson('/auth/me');
        $response->assertOk();
    }

    /**
     * ログインしていない場合meにアクセスできないことのテスト
     */
    public function test_me_cant_access_not_logged_in(): void
    {
        User::factory()->create();
        $response = $this->getJson('/auth/me');
        $response->assertUnauthorized();
    }

    /**
     * ログアウトしてからmeにアクセスできないことのテスト
     */
    public function test_logout_and_me_cant_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        // ログイン処理
        $loginRequestBody = [
            'email' => $user->email,
            'password' => 'password',
        ];
        $loginResponse = $this->postJson('/auth/login', $loginRequestBody);
        $authHeader = 'Bearer ' . $loginResponse->json()['access_token'];

        // ログアウト処理
        $logoutResponse = $this->postJson('/auth/logout', [], [
            'Authorization' => $authHeader,
        ]);
        $logoutResponse->assertOk();

        // ログアウト後のmeアクセス
        $meResponse = $this->getJson('/auth/me', [
            'Authorization' => $authHeader,
        ]);
        $meResponse->assertUnauthorized();
    }
}
