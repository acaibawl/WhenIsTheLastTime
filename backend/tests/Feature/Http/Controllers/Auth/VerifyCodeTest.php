<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 認証コード検証・登録完了APIのテスト
 */
class VerifyCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Redisのクリーンアップ
        Redis::flushdb();
    }

    /**
     * 認証コード検証成功・ユーザー登録完了のテスト
     */
    #[Test]
    public function test_verify_code_success(): void
    {
        $email = 'newuser@example.com';
        $code = '123456';
        $password = 'password123';
        $nickname = 'NewUser';

        // Redisに登録データを保存
        $registrationData = [
            'email' => $email,
            'passwordHash' => Hash::make($password),
            'nickname' => $nickname,
            'code' => Hash::make($code),
            'attempts' => 0,
            'lastSentAt' => time(),
            'createdAt' => time(),
            'isExistingUser' => false,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
            'code' => $code,
        ];

        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', true)
            ->has('data', fn (AssertableJson $data) => $data
                ->has('user', fn (AssertableJson $user) => $user
                    ->where('email', $email)
                    ->where('nickname', $nickname)
                    ->has('id')
                    ->has('createdAt')
                )
                ->has('accessToken')
            )
            ->has('meta')
        );

        // ユーザーが作成されたことを確認
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'nickname' => $nickname,
        ]);

        // Redisからデータが削除されたことを確認
        $this->assertNull(Redis::get("registration:{$email}"));
    }

    /**
     * 認証コードが存在しない場合のテスト
     */
    #[Test]
    public function test_verify_code_not_found(): void
    {
        $requestBody = [
            'email' => 'notfound@example.com',
            'code' => '123456',
        ];

        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertStatus(400);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'INVALID_VERIFICATION_CODE')
                ->where('message', '認証コードが正しくありません')
            )
        );
    }

    /**
     * 認証コードが誤っている場合のテスト
     */
    #[Test]
    public function test_verify_code_invalid(): void
    {
        $email = 'user@example.com';
        $correctCode = '123456';
        $wrongCode = '654321';

        // Redisに登録データを保存
        $registrationData = [
            'email' => $email,
            'passwordHash' => Hash::make('password123'),
            'nickname' => 'User',
            'code' => Hash::make($correctCode),
            'attempts' => 0,
            'lastSentAt' => time(),
            'createdAt' => time(),
            'isExistingUser' => false,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
            'code' => $wrongCode,
        ];

        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertStatus(400);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'INVALID_VERIFICATION_CODE')
                ->where('message', '認証コードが正しくありません')
            )
        );

        // 試行回数がインクリメントされたことを確認
        $data = Redis::get("registration:{$email}");
        $this->assertNotNull($data);
        $updatedData = json_decode($data, true);
        $this->assertEquals(1, $updatedData['attempts']);
    }

    /**
     * 試行回数超過のテスト
     */
    #[Test]
    public function test_verify_code_too_many_attempts(): void
    {
        $email = 'user@example.com';
        $code = '123456';

        // Redisに登録データを保存（試行回数が上限）
        $registrationData = [
            'email' => $email,
            'passwordHash' => Hash::make('password123'),
            'nickname' => 'User',
            'code' => Hash::make($code),
            'attempts' => 5,
            'lastSentAt' => time(),
            'createdAt' => time(),
            'isExistingUser' => false,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
            'code' => $code,
        ];

        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertStatus(429);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'TOO_MANY_ATTEMPTS')
                ->where('message', '試行回数が上限に達しました。しばらくしてからお試しください')
            )
        );
    }

    /**
     * 既存ユーザーフラグが立っている場合のテスト
     */
    #[Test]
    public function test_verify_code_existing_user(): void
    {
        $email = 'existing@example.com';
        $code = '123456';

        // 既存ユーザーを作成
        User::factory()->create(['email' => $email]);

        // Redisに登録データを保存（既存ユーザーフラグ）
        $registrationData = [
            'email' => $email,
            'passwordHash' => '',
            'nickname' => '',
            'code' => Hash::make($code),
            'attempts' => 0,
            'lastSentAt' => time(),
            'createdAt' => time(),
            'isExistingUser' => true,
        ];
        Redis::setex("registration:{$email}", 600, json_encode($registrationData));

        $requestBody = [
            'email' => $email,
            'code' => $code,
        ];

        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertStatus(400);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('success', false)
            ->has('error', fn (AssertableJson $error) => $error
                ->where('code', 'INVALID_VERIFICATION_CODE')
                ->where('message', '認証コードが正しくありません')
            )
        );

        // Redisからデータが削除されたことを確認
        $this->assertNull(Redis::get("registration:{$email}"));
    }

    /**
     * バリデーションエラーのテスト
     */
    #[Test]
    #[DataProvider('dataProviderInvalidParameters')]
    public function test_verify_code_validation_error(array $requestBody, string $expectedMessage): void
    {
        $response = $this->postJson('/auth/register/verify', $requestBody);

        $response->assertUnprocessable();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('message', $expectedMessage)
            ->etc()
        );
    }

    /**
     * @return array[]
     */
    public static function dataProviderInvalidParameters(): array
    {
        return [
            'email空文字' => [
                'requestBody' => [
                    'email' => '',
                    'code' => '123456',
                ],
                'expectedMessage' => 'メールアドレスを入力してください',
            ],
            'emailフォーマット誤り' => [
                'requestBody' => [
                    'email' => 'invalid-email',
                    'code' => '123456',
                ],
                'expectedMessage' => '有効なメールアドレスを入力してください',
            ],
            'code空文字' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'code' => '',
                ],
                'expectedMessage' => '認証コードを入力してください',
            ],
            'code形式誤り（数字以外）' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'code' => 'abcdef',
                ],
                'expectedMessage' => '認証コードは数字で入力してください',
            ],
            'code桁数誤り（5桁）' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'code' => '12345',
                ],
                'expectedMessage' => '認証コードは6桁で入力してください',
            ],
            'code桁数誤り（7桁）' => [
                'requestBody' => [
                    'email' => 'user@example.com',
                    'code' => '1234567',
                ],
                'expectedMessage' => '認証コードは6桁で入力してください',
            ],
        ];
    }
}
